// @flow
import Range from '@ckeditor/ckeditor5-engine/src/model/range';
import Position from '@ckeditor/ckeditor5-engine/src/model/position';

/*
 * Returns a range containing the entire link in which the given `position` is placed.
 *
 * It can be used e.g. to get the entire range on which the `linkHref` attribute needs to be changed when having a
 * selection inside a link.
 */
export default function findLinkRange(position: Position, value: Object, internalAttribute: string): Range {
    return new Range(
        _findBound(position, value, true, internalAttribute),
        _findBound(position, value, false, internalAttribute)
    );
}

/*
 * Walks forward or backward (depends on the `lookBack` flag),
 * node by node,as long as they have the same `linkHref` attribute value
 * and returns a position just before or after (depends on the `lookBack` flag) the last matched node.
 */
function _findBound(position: Position, value: Object, lookBack: boolean, internalAttribute: string): Position {
    // Get node before or after position (depends on `lookBack` flag).
    // When position is inside text node then start searching from text node.
    let node =
        position.textNode ||
        (lookBack ? position.nodeBefore : position.nodeAfter);

    let lastNode = null;

    while (node && node.getAttribute(internalAttribute) == value) {
        lastNode = node;
        node = lookBack ? node.previousSibling : node.nextSibling;
    }

    return lastNode
        ? Position.createAt(lastNode, lookBack ? 'before' : 'after')
        : position;
}
