// @flow
function addLinkConversion(editor: Object, tag: string, internalAttribute: string, tagAttribute: string) {
    editor.model.schema.extend('$text', {allowAttributes: internalAttribute});

    editor.conversion.for('upcast').attributeToAttribute({
        view: {
            name: tag,
            key: tagAttribute,
        },
        model: internalAttribute,
    });

    editor.conversion.for('downcast').attributeToElement({
        model: internalAttribute,
        view: (attributeValue, writer) => {
            return writer.createAttributeElement(tag, {[tagAttribute]: attributeValue});
        },
    });
}

function findModelItemInSelection(editor: Object) {
    const firstPosition = editor.model.document.selection.getFirstPosition();
    return firstPosition.textNode || firstPosition.nodeBefore;
}

function findViewLinkItemInSelection(editor: Object, linkTag: string) {
    const selection = editor.editing.view.document.selection;
    const firstPosition = selection.getFirstPosition();

    return firstPosition.getAncestors().find(
        (ancestor) => ancestor.is('attributeElement') && ancestor.name === linkTag
    );
}

export {addLinkConversion, findModelItemInSelection, findViewLinkItemInSelection};
