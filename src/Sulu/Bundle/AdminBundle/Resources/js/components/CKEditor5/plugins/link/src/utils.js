// @flow
import Node from '@ckeditor/ckeditor5-engine/src/view/node';
import AttributeElement from '@ckeditor/ckeditor5-engine/src/view/attributeelement';
import {URL_REGEX} from '../../../../Url/Url';

const linkElementSymbol = Symbol('linkElement');

/* eslint-disable no-control-regex */
const ATTRIBUTE_WHITESPACES = /[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205f\u3000]/g;
/* eslint-enable */

/*
 * Returns `true` if a given view node is the link element.
 */
export function isLinkElement(node: Node): boolean {
    return (
        node.is('attributeElement') &&
        !!node.getCustomProperty(linkElementSymbol)
    );
}

/*
 * Creates link AttributeElement with provided `href` attribute.
 */
export function createLinkElement(
    writer: Object,
    tag: string,
    attributes: Object,
    internalAttribute: string
): AttributeElement {
    // Priority 5 - https://github.com/ckeditor/ckeditor5-link/issues/121.
    const linkElement = writer.createAttributeElement(
        tag,
        {...attributes},
        {priority: 5}
    );
    writer.setCustomProperty(linkElementSymbol, true, linkElement);
    writer.setCustomProperty(
        'internalAttribute',
        internalAttribute,
        linkElement
    );

    return linkElement;
}

/*
 * Returns a safe URL based on a given value.
 *
 * An URL is considered safe if it is safe for the user (does not contain any malicious code).
 *
 * If URL is considered unsafe, a simple `"#"` is returned.
 */
export function ensureSafeUrl(url: string): string {
    return isSafeUrl(url) ? url : '#';
}

/*
 * Checks whether the given URL is safe for the user (does not contain any malicious code).
 */
export function isSafeUrl(url: string): boolean {
    const normalizedUrl = url.replace(ATTRIBUTE_WHITESPACES, '');

    return !!normalizedUrl.match(URL_REGEX);
}
