// @flow
function addLinkConversion(editor: Object, tag: string, internalAttribute: string, tagAttribute: string) {
    // implementation is inspired by the official ckeditor5-link package:
    // https://github.com/ckeditor/ckeditor5/blob/v23.0.0/packages/ckeditor5-link/src/linkediting.js

    editor.model.schema.extend('$text', {allowAttributes: internalAttribute});

    editor.conversion.for('upcast').elementToAttribute({
        view: {
            name: tag,
            attributes: {
                [tagAttribute]: true,
            },
        },
        model: {
            key: internalAttribute,
            value: (viewElement) => viewElement.getAttribute(tagAttribute),
        },
    });

    editor.conversion.for('downcast').attributeToElement({
        model: internalAttribute,
        view: (attributeValue, {writer}) => {
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

function removePTags(htmlString: string): string {
    const string = htmlString.replace(/<p>/g, '<!--p-->').replace(/<\/p>/g, '<!--/p--><br></br>');
    return replaceLast(string, '<br></br>', '');
}

function addPTags(htmlString: string): string {
    return htmlString
        .replace(/<!--p-->/g, '<p>')
        .replace(/<!--\/p--><br><\/br>/g, '</p>')
        .replace(/<!--\/p-->/g, '</p>');
}

function replaceLast(str, search, replace) {
    const lastIndex = str.lastIndexOf(search);

    if (lastIndex === -1) {
        return str;
    }

    return str.slice(0, lastIndex) + replace + str.slice(lastIndex + search.length);
}

export {addLinkConversion, findModelItemInSelection, findViewLinkItemInSelection, removePTags, addPTags};
