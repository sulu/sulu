// @flow
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import {downcastAttributeToElement} from '@ckeditor/ckeditor5-engine/src/conversion/downcast-converters';
import {upcastElementToAttribute} from '@ckeditor/ckeditor5-engine/src/conversion/upcast-converters';
import bindTwoStepCaretToAttribute from '@ckeditor/ckeditor5-engine/src/utils/bindtwostepcarettoattribute';
import LinkCommand from './linkcommand';
import UnlinkCommand from './unlinkcommand';
import {createLinkElement, ensureSafeUrl} from './utils';
import findLinkRange from './findlinkrange';

const HIGHLIGHT_CLASS = 'ck-link_selected';

export default function(
    commandPrefix: string,
    internalAttribute: string,
    internalAttributes: string[],
    tag: string,
    attributeName: string,
    validateURL: boolean = false
) {
    /*
     * The link engine feature.
     *
     * It introduces the `linkHref="url"` attribute in the model which renders to the view as a `<a href="url">` element
     * as well as `'link'` and `'unlink'` commands.
     */
    return class LinkEditing extends Plugin {
        init() {
            const editor = this.editor;

            // Allow link attribute on all inline nodes.
            editor.model.schema.extend('$text', {
                allowAttributes: internalAttribute,
            });

            editor.conversion.for('dataDowncast').add(
                downcastAttributeToElement({
                    model: internalAttribute,
                    view: (value, writer) => {
                        if (!value) {
                            value = {
                                value: '',
                                attributes: {},
                            };
                        }

                        return createLinkElement(
                            writer,
                            tag,
                            {
                                ...value.attributes,
                                [attributeName]: value.value,
                            },
                            internalAttribute
                        );
                    },
                })
            );

            editor.conversion.for('editingDowncast').add(
                downcastAttributeToElement({
                    model: internalAttribute,
                    view: (value, writer) => {
                        if (!value) {
                            value = {
                                value: '',
                                attributes: {},
                            };
                        }

                        return createLinkElement(
                            writer,
                            tag,
                            {
                                ...value.attributes,
                                [attributeName]: validateURL
                                    ? ensureSafeUrl(value.value)
                                    : value.value,
                            },
                            internalAttribute
                        );
                    },
                })
            );

            editor.conversion.for('upcast').add(
                upcastElementToAttribute({
                    view: {
                        name: tag,
                        attributes: {
                            [attributeName]: true,
                        },
                    },
                    model: {
                        key: internalAttribute,
                        value: (viewElement) => {
                            const value = {
                                value: viewElement.getAttribute(attributeName),
                                attributes: {},
                            };

                            for (const attr of viewElement.getAttributes()) {
                                const key = attr[0];
                                const val = attr[1];

                                if (!['class', attributeName].includes(key)) {
                                    value.attributes[key] = val;
                                }
                            }

                            return value;
                        },
                    },
                })
            );

            const CustomLinkCommand = LinkCommand(
                internalAttribute,
                internalAttributes
            );
            const CustomUnlinkCommand = UnlinkCommand(internalAttributes);

            // Create linking commands.
            editor.commands.add(
                commandPrefix + '_link',
                new CustomLinkCommand(editor)
            );
            editor.commands.add(
                commandPrefix + '_unlink',
                new CustomUnlinkCommand(editor)
            );

            // Enable two-step caret movement for `linkHref` attribute.
            bindTwoStepCaretToAttribute(
                editor.editing.view,
                editor.model,
                this,
                internalAttribute
            );

            // Setup highlight over selected link.
            this._setupLinkHighlight();
        }

        /*
         * Adds a visual highlight style to a link in which the selection is anchored.
         * Together with two-step caret movement, they indicate that the user is typing inside the link.
         *
         * Highlight is turned on by adding `.ck-link_selected` class to the link in the view:
         *
         * * the class is removed before conversion has started, as callbacks added with `'highest'` priority
         * to DowncastDispatcher events,
         * * the class is added in the view post fixer, after other changes in the model tree were converted
         * to the view.
         *
         * This way, adding and removing highlight does not interfere with conversion.
         */
        _setupLinkHighlight() {
            const editor = this.editor;
            const view = editor.editing.view;
            const highlightedLinks = new Set();

            // Adding the class.
            view.document.registerPostFixer((writer) => {
                const selection = editor.model.document.selection;

                if (selection.hasAttribute(internalAttribute)) {
                    const modelRange = findLinkRange(
                        selection.getFirstPosition(),
                        selection.getAttribute(internalAttribute),
                        internalAttribute
                    );
                    const viewRange = editor.editing.mapper.toViewRange(
                        modelRange
                    );

                    // There might be multiple `a` elements in the `viewRange`, for example, when the `a` element is
                    // broken by a UIElement.
                    for (const item of viewRange.getItems()) {
                        if (item.is(tag)) {
                            writer.addClass(HIGHLIGHT_CLASS, item);
                            highlightedLinks.add(item);
                        }
                    }
                }
            });

            // Removing the class.
            editor.conversion.for('editingDowncast').add((dispatcher) => {
                // Make sure the highlight is removed on every possible event, before conversion is started.
                dispatcher.on('insert', removeHighlight, {
                    priority: 'highest',
                });
                dispatcher.on('remove', removeHighlight, {
                    priority: 'highest',
                });
                dispatcher.on('attribute', removeHighlight, {
                    priority: 'highest',
                });
                dispatcher.on('selection', removeHighlight, {
                    priority: 'highest',
                });

                function removeHighlight() {
                    view.change((writer) => {
                        for (const item of highlightedLinks.values()) {
                            writer.removeClass(HIGHLIGHT_CLASS, item);
                            highlightedLinks.delete(item);
                        }
                    });
                }
            });
        }
    };
}
