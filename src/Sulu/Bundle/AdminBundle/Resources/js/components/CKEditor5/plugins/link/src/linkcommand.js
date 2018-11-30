/**
 * @license Copyright (c) 2003-2018, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md.
 */

/**
 * @module link/linkcommand
 */

import Command from '@ckeditor/ckeditor5-core/src/command';
import Range from '@ckeditor/ckeditor5-engine/src/model/range';
import findLinkRange from './findlinkrange';
import toMap from '@ckeditor/ckeditor5-utils/src/tomap';

/**
 * @param {String} internalAttribute The internal attribute key (Default `linkHref`)
 * @param {Array} internalAttributes A list of all registered internalAttributes
 */
export default function(internalAttribute, internalAttributes) {
    /**
     * The link command. It is used by the {@createLinkPlugin module:link/link~Link createLinkPlugin feature}.
     *
     * @extends module:core/command~Command
     */
    return class LinkCommand extends Command {
        /**
         * The value of the `internalAttribute` attribute if the start of the selection is located in a node with this attribute.
         *
         * @observable
         * @readonly
         * @member {Object|undefined} #value
         */

        /**
         * @inheritDoc
         */
        refresh() {
            const model = this.editor.model;
            const doc = model.document;

            this.value = doc.selection.getAttribute(internalAttribute);
            this.isEnabled = model.schema.checkAttributeInSelection(
                doc.selection,
                internalAttribute,
            );
        }

        /**
         * Executes the command.
         *
         * When the selection is non-collapsed, the `linkHref` attribute will be applied to nodes inside the selection, but only to
         * those nodes where the `linkHref` attribute is allowed (disallowed nodes will be omitted).
         *
         * When the selection is collapsed and is not inside the text with the `linkHref` attribute, the
         * new {@link module:engine/model/text~Text Text node} with the `linkHref` attribute will be inserted in place of caret, but
         * only if such element is allowed in this place. The `_data` of the inserted text will equal the `href` parameter.
         * The selection will be updated to wrap the just inserted text node.
         *
         * When the selection is collapsed and inside the text with the `linkHref` attribute, the attribute value will be updated.
         *
         * @fires execute
         * @param {String} value Link destination.
         */
        execute(value) {
            const model = this.editor.model;
            const selection = model.document.selection;

            model.change((writer) => {
                // If selection is collapsed then update selected link or insert new one at the place of caret.
                if (selection.isCollapsed) {
                    const position = selection.getFirstPosition();
                    let internalAttr = internalAttributes.find((attr) =>
                        selection.hasAttribute(attr),
                    );

                    // When selection is inside text with `linkHref` attribute.
                    if (internalAttr) {
                        // Then update `linkHref` value.
                        const linkRange = findLinkRange(
                            selection.getFirstPosition(),
                            selection.getAttribute(internalAttr),
                            internalAttr,
                        );

                        internalAttributes.forEach((attr) => {
                            writer.removeAttribute(attr, linkRange);
                        });

                        writer.setAttribute(
                            internalAttribute,
                            value,
                            linkRange,
                        );

                        // Create new range wrapping changed link.
                        writer.setSelection(linkRange);
                    }
                    // If not then insert text node with `linkHref` attribute in place of caret.
                    // However, since selection in collapsed, attribute value will be used as data for text node.
                    // So, if `href` is empty, do not create text node.
                    else if (value && value.value) {
                        const attributes = toMap(selection.getAttributes());

                        Object.entries(value.attributes).forEach((entry) => {
                            attributes.set(entry[0], entry[1]);
                        });

                        attributes.set(internalAttribute, value.value);

                        const node = writer.createText(value.value, attributes);

                        writer.insert(node, position);

                        // Create new range wrapping created node.
                        writer.setSelection(Range.createOn(node));
                    }
                } else {
                    // If selection has non-collapsed ranges, we change attribute on nodes inside those ranges
                    // omitting nodes where `linkHref` attribute is disallowed.
                    const ranges = model.schema.getValidRanges(
                        selection.getRanges(),
                        internalAttribute,
                    );

                    for (const range of ranges) {
                        internalAttributes.forEach((attr) => {
                            writer.removeAttribute(attr, range);
                        });

                        writer.setAttribute(internalAttribute, value, range);
                    }
                }
            });
        }
    };
}
