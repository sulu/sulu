/**
 * @license Copyright (c) 2003-2018, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md.
 */

/**
 * @module link/utils
 */

const linkElementSymbol = Symbol('linkElement');

const ATTRIBUTE_WHITESPACES = /[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205f\u3000]/g; // eslint-disable-line no-control-regex
const SAFE_URL = /^(?:(?:https?|ftps?|mailto):|[^a-z]|[a-z+.-]+(?:[^a-z+.:-]|$))/i;

/**
 * Returns `true` if a given view node is the link element.
 *
 * @param {module:engine/view/node~Node} node
 * @returns {Boolean}
 */
export function isLinkElement(node) {
    return (
        node.is('attributeElement') &&
        !!node.getCustomProperty(linkElementSymbol)
    );
}

// param {String} attributeName The attribute name (Default: href)
// param {String} attributeValue The output attribute value

/**
 * Creates link {@createLinkPlugin module:engine/view/attributeelement~AttributeElement} with provided `href` attribute.
 *
 * @param {Object} writer
 * @param {String} tag The output tag name (Default: a)
 * @param {Object} attributes The output attribute value
 * @param {String} internalAttribute The internal attribute key (Default `linkHref`)
 * @returns {module:engine/view/attributeelement~AttributeElement}
 */
export function createLinkElement(writer, tag, attributes, internalAttribute) {
    // Priority 5 - https://github.com/ckeditor/ckeditor5-link/issues/121.
    const linkElement = writer.createAttributeElement(
        tag,
        { ...attributes },
        { priority: 5 },
    );
    writer.setCustomProperty(linkElementSymbol, true, linkElement);
    writer.setCustomProperty(
        'internalAttribute',
        internalAttribute,
        linkElement,
    );

    return linkElement;
}

/**
 * Returns a safe URL based on a given value.
 *
 * An URL is considered safe if it is safe for the user (does not contain any malicious code).
 *
 * If URL is considered unsafe, a simple `"#"` is returned.
 *
 * @protected
 * @param {*} url
 * @returns {String} Safe URL.
 */
export function ensureSafeUrl(url) {
    url = String(url);

    return isSafeUrl(url) ? url : '#';
}

// Checks whether the given URL is safe for the user (does not contain any malicious code).
//
// @param {String} url URL to check.
function isSafeUrl(url) {
    const normalizedUrl = url.replace(ATTRIBUTE_WHITESPACES, '');

    return normalizedUrl.match(SAFE_URL);
}
