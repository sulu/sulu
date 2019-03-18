// @flow
const LINK_HREF_ATTRIBUTE = 'linkHref';
const LINK_TARGET_ATTRIBUTE = 'linkTarget';

function hasExternalLinkAttribute(node: ?Object) {
    if (!node || !node.hasAttribute) {
        return false;
    }

    return node.hasAttribute(LINK_HREF_ATTRIBUTE) || node.hasAttribute(LINK_TARGET_ATTRIBUTE);
}

export {LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE, hasExternalLinkAttribute};
