/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-env node */
'use strict';

const DECORATOR_NAMES = new Set(['observable', 'action', 'computed', 'inject']);

function getDecoratorBaseName(expression) {
    if (!expression) {
        return undefined;
    }

    if (expression.type === 'Identifier') {
        return expression.name;
    }

    if (expression.type === 'CallExpression') {
        return getDecoratorBaseName(expression.callee);
    }

    if (expression.type === 'MemberExpression') {
        return getDecoratorBaseName(expression.object);
    }

    return undefined;
}

function hasRelevantDecorators(decorators) {
    if (!decorators || decorators.length === 0) {
        return false;
    }

    return decorators.some((decorator) => DECORATOR_NAMES.has(getDecoratorBaseName(decorator.expression)));
}

function classHasMobxDecorators(classNode) {
    if (hasRelevantDecorators(classNode.decorators)) {
        return true;
    }

    return classNode.body.body.some((member) => hasRelevantDecorators(member.decorators));
}

function getClassConstructor(classNode) {
    return classNode.body.body.find((member) => member.kind === 'constructor');
}

function getMethodBody(methodNode) {
    if (methodNode && methodNode.body && Array.isArray(methodNode.body.body)) {
        return methodNode.body;
    }

    if (methodNode && methodNode.value && methodNode.value.body && Array.isArray(methodNode.value.body.body)) {
        return methodNode.value.body;
    }

    return undefined;
}

function buildMakeObservableGuard(j) {
    return j.ifStatement(
        j.binaryExpression(
            '===',
            j.unaryExpression('typeof', j.identifier('makeObservable'), true),
            j.literal('function')
        ),
        j.blockStatement([
            j.expressionStatement(
                j.callExpression(j.identifier('makeObservable'), [j.thisExpression()])
            ),
        ])
    );
}

function methodContainsMakeObservableCall(j, methodBody) {
    return j(methodBody)
        .find(j.CallExpression, {
            callee: {
                type: 'Identifier',
                name: 'makeObservable',
            },
        })
        .filter((path) => path.node.arguments.length > 0 && path.node.arguments[0].type === 'ThisExpression')
        .size() > 0;
}

function findInsertIndexAfterSuper(statements) {
    for (let i = 0; i < statements.length; i++) {
        const statement = statements[i];

        if (
            statement.type === 'ExpressionStatement'
            && statement.expression
            && statement.expression.type === 'CallExpression'
            && statement.expression.callee
            && statement.expression.callee.type === 'Super'
        ) {
            return i + 1;
        }
    }

    return 0;
}

function createConstructor(j, hasSuperClass) {
    const statements = [];
    const params = [];

    if (hasSuperClass) {
        params.push(j.restElement(j.identifier('args')));
        statements.push(
            j.expressionStatement(
                j.callExpression(j.super(), [j.spreadElement(j.identifier('args'))])
            )
        );
    }

    statements.push(buildMakeObservableGuard(j));

    return j.classMethod(
        'constructor',
        j.identifier('constructor'),
        params,
        j.blockStatement(statements)
    );
}

function ensureMakeObservableImport(j, root) {
    const mobxImports = root.find(j.ImportDeclaration, {
        source: {
            value: 'mobx',
        },
    });

    const hasNamedImport = mobxImports
        .filter((path) =>
            path.node.importKind !== 'type'
            && (path.node.specifiers || []).some(
                (specifier) =>
                    specifier.type === 'ImportSpecifier'
                    && specifier.imported
                    && specifier.imported.type === 'Identifier'
                    && specifier.imported.name === 'makeObservable'
            )
        )
        .size() > 0;

    if (hasNamedImport) {
        return false;
    }

    const importSpecifier = j.importSpecifier(j.identifier('makeObservable'));
    const writableMobxImport = mobxImports
        .filter((path) => {
            if (path.node.importKind === 'type') {
                return false;
            }

            const specifiers = path.node.specifiers || [];

            return !specifiers.some((specifier) => specifier.type === 'ImportNamespaceSpecifier');
        })
        .at(0);

    if (writableMobxImport.size() > 0) {
        const path = writableMobxImport.get();
        path.node.specifiers = path.node.specifiers || [];
        path.node.specifiers.push(importSpecifier);
        return true;
    }

    const newImport = j.importDeclaration([importSpecifier], j.literal('mobx'));
    const body = root.get().node.program.body;

    const lastImportIndex = body.reduce(
        (lastIndex, node, index) => (node.type === 'ImportDeclaration' ? index : lastIndex),
        -1
    );

    if (lastImportIndex === -1) {
        body.unshift(newImport);
    } else {
        body.splice(lastImportIndex + 1, 0, newImport);
    }

    return true;
}

module.exports = function transformer(file, api) {
    const j = api.jscodeshift;
    const root = j(file.source);

    const classes = [];

    root.find(j.ClassDeclaration).forEach((path) => {
        if (classHasMobxDecorators(path.node)) {
            classes.push(path.node);
        }
    });

    root.find(j.ClassExpression).forEach((path) => {
        if (classHasMobxDecorators(path.node)) {
            classes.push(path.node);
        }
    });

    if (classes.length === 0) {
        return file.source;
    }

    let hasChanges = false;

    classes.forEach((classNode) => {
        let constructorMethod = getClassConstructor(classNode);

        if (!constructorMethod) {
            constructorMethod = createConstructor(j, Boolean(classNode.superClass));
            classNode.body.body.unshift(constructorMethod);
            hasChanges = true;
            return;
        }

        const constructorBody = getMethodBody(constructorMethod);

        if (!constructorBody || methodContainsMakeObservableCall(j, constructorBody)) {
            return;
        }

        const insertIndex = classNode.superClass ? findInsertIndexAfterSuper(constructorBody.body) : 0;
        constructorBody.body.splice(insertIndex, 0, buildMakeObservableGuard(j));
        hasChanges = true;
    });

    if (ensureMakeObservableImport(j, root)) {
        hasChanges = true;
    }

    return hasChanges ? root.toSource({quote: 'single'}) : file.source;
};

module.exports.parser = 'babel';
