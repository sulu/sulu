// @flow
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import Range from '@ckeditor/ckeditor5-engine/src/view/range';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';

import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';

import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import AttributeElement from '@ckeditor/ckeditor5-engine/src/view/attributeelement';
import Position from '@ckeditor/ckeditor5-engine/src/view/position';
import defaultLinkIcon from '../theme/icons/link.svg';
import LinkActionsView from './ui/linkactionsview';

import {isLinkElement} from './utils';

export default function(
    pluginName: string,
    commandPrefix: string,
    internalAttribute: string,
    tag: string,
    attributeName: string,
    onLink: (
        setValue: (value: any) => void,
        currentValue: any
    ) => void,
    validateURL: boolean = false,
    linkIcon: string = defaultLinkIcon,
    linkKeystroke: ?string = undefined
) {
    /*
     * The link UI plugin. It introduces the `'createLinkPlugin'` and `'unlink'` buttons
     * and support for the <kbd>Ctrl+K</kbd> keystroke.
     *
     * It uses the ContextualBalloon plugin.
     */
    return class LinkUI extends Plugin {
        /*
         * The actions view displayed inside of the balloon.
         */
        actionsView: any;

        /*
         * The contextual balloon plugin instance.
         */
        _balloon: ContextualBalloon;

        static get requires() {
            return [ContextualBalloon];
        }

        init() {
            const editor = this.editor;

            editor.editing.view.addObserver(ClickObserver);

            this.actionsView = this._createActionsView();

            this._balloon = editor.plugins.get(ContextualBalloon);

            // Create toolbar buttons.
            this._createToolbarLinkButton();

            // Attach lifecycle actions to the the balloon.
            this._enableUserBalloonInteractions();

            this.set('value');
        }

        /*
         * Creates the LinkActionsView instance.
         */
        _createActionsView(): any {
            const editor = this.editor;
            const CustomLinkActionsView = LinkActionsView(
                tag,
                attributeName,
                validateURL
            );
            const actionsView = new CustomLinkActionsView(editor.locale);
            const linkCommand = editor.commands.get(commandPrefix + '_link');
            const unlinkCommand = editor.commands.get(
                commandPrefix + '_unlink'
            );

            this.bind('value').to(linkCommand, 'value');

            actionsView.bind('href').to(linkCommand, 'value');
            actionsView.editButtonView.bind('isEnabled').to(linkCommand);
            actionsView.unlinkButtonView.bind('isEnabled').to(unlinkCommand);

            // Execute unlink command after clicking on the "Edit" button.
            this.listenTo(actionsView, 'edit', () => {
                this._addFormView();
            });

            // Execute unlink command after clicking on the "Unlink" button.
            this.listenTo(actionsView, 'unlink', () => {
                editor.execute(commandPrefix + '_unlink');
                this._hideUI();
            });

            // Close the panel on esc key press when the **actions have focus**.
            actionsView.keystrokes.set('Esc', (data, cancel) => {
                this._hideUI();
                cancel();
            });

            if (linkKeystroke) {
                // Open the form view on Ctrl+K when the **actions have focus**..
                actionsView.keystrokes.set(linkKeystroke, (data, cancel) => {
                    this._addFormView();
                    cancel();
                });
            }

            return actionsView;
        }

        /*
         * Creates a toolbar Link button. Clicking this button will show
         * a balloon attached to the selection.
         */
        _createToolbarLinkButton() {
            const editor = this.editor;
            const linkCommand = editor.commands.get(commandPrefix + '_link');

            if (linkKeystroke) {
                // Handle the `Ctrl+K` keystroke and show the panel.
                editor.keystrokes.set(linkKeystroke, (keyEvtData, cancel) => {
                    // Prevent focusing the search bar in FF and opening new tab in Edge. #153, #154.
                    cancel();

                    if (linkCommand.isEnabled) {
                        this._showUI();
                    }
                });
            }

            editor.ui.componentFactory.add(
                commandPrefix + '_link',
                (locale) => {
                    const button = new ButtonView(locale);

                    button.isEnabled = true;
                    button.label = pluginName;
                    button.icon = linkIcon;
                    button.tooltip = true;

                    if (linkKeystroke) {
                        linkKeystroke;
                    }

                    // Bind button to the command.
                    button
                        .bind('isOn', 'isEnabled')
                        .to(linkCommand, 'value', 'isEnabled');

                    // Show the panel on button click.
                    this.listenTo(button, 'execute', () => this._showUI(true));

                    return button;
                }
            );
        }

        /*
         * Attaches actions that control whether the balloon panel containing the
         * formView is visible or not.
         */
        _enableUserBalloonInteractions() {
            const viewDocument = this.editor.editing.view.document;

            // Handle click on view document and show panel when selection is placed inside the link element.
            // Keep panel open until selection will be inside the same link element.
            this.listenTo(viewDocument, 'click', () => {
                const parentLink = this._getSelectedLinkElement();

                if (parentLink) {
                    // Then show panel but keep focus inside editor editable.
                    this._showUI();
                }
            });

            // Focus the form if the balloon is visible and the Tab key has been pressed.
            this.editor.keystrokes.set(
                'Tab',
                (data, cancel) => {
                    if (
                        this._areActionsVisible &&
                        !this.actionsView.focusTracker.isFocused
                    ) {
                        this.actionsView.focus();
                        cancel();
                    }
                },
                {
                    // Use the high priority because the link UI navigation is more important
                    // than other feature's actions, e.g. list indentation.
                    // https://github.com/ckeditor/ckeditor5-link/issues/146
                    priority: 'high',
                }
            );

            // Close the panel on the Esc key press when the editable has focus and the balloon is visible.
            this.editor.keystrokes.set('Esc', (data, cancel) => {
                if (this._areActionsVisible) {
                    this._hideUI();
                    cancel();
                }
            });

            // Close on click outside of balloon panel element.
            clickOutsideHandler({
                emitter: this.actionsView,
                activator: () => this._areActionsVisible,
                contextElements: [this._balloon.view.element],
                callback: () => this._hideUI(),
            });
        }

        /*
         * Adds the actionView to the balloon.
         */
        _addActionsView() {
            if (this._areActionsInPanel) {
                return;
            }

            this._balloon.add({
                view: this.actionsView,
                position: this._getBalloonPositionData(),
            });
        }

        /*
         * Adds the formView to the balloon.
         */
        _addFormView() {
            this._hideUI();

            const editor = this.editor;

            const setValue = (newValue) => {
                if (newValue && newValue.value) {
                    editor.execute(commandPrefix + '_link', newValue);
                    this._showUI();
                }
            };

            onLink(setValue, this.value);
        }

        /*
         * Shows the right kind of the UI for current state of the command. It's either
         * formView or actionsView..
         */
        _showUI(formView: boolean = false) {
            const editor = this.editor;
            const linkCommand = editor.commands.get(commandPrefix + '_link');

            if (!linkCommand.isEnabled) {
                return;
            }

            this._hideUI();

            // When there's no link under the selection, go straight to the editing UI.
            if (!this._getSelectedLinkElement()) {
                this._addFormView();
                return;
            }
            // If theres a link under the selection...
            else {
                if (
                    this._getSelectedLinkElement().getCustomProperty(
                        'internalAttribute'
                    ) !== internalAttribute
                ) {
                    if (formView) {
                        this._addFormView();
                    }

                    return;
                }

                this._addActionsView();
            }

            // Begin responding to ui#update once the UI is added.
            this._startUpdatingUI();
        }

        /*
         * Removes the actionsView from the balloon.
         */
        _hideUI() {
            if (!this._areActionsInPanel) {
                return;
            }

            const editor = this.editor;

            this.stopListening(editor.ui, 'update');

            // Then remove the actions view because it's beneath the form.
            this._balloon.remove(this.actionsView);

            // Make sure the focus always gets back to the editable.
            editor.editing.view.focus();
        }

        /*
         * Makes the UI react to the EditorUI update event to
         * reposition itself when the editor ui should be refreshed.
         */
        _startUpdatingUI() {
            const editor = this.editor;
            const viewDocument = editor.editing.view.document;

            let prevSelectedLink = this._getSelectedLinkElement();
            let prevSelectionParent = getSelectionParent();

            this.listenTo(editor.ui, 'update', () => {
                const selectedLink = this._getSelectedLinkElement();
                const selectionParent = getSelectionParent();

                // Hide the panel if:
                //
                // * the selection went out of the EXISTING link element. E.g. user moved the caret out
                //   of the link,
                // * the selection went to a different parent when creating a NEW link. E.g. someone
                //   else modified the document.
                // * the selection has expanded (e.g. displaying link actions then pressing SHIFT+Right arrow).
                //
                // Note: #_getSelectedLinkElement will return a link for a non-collapsed selection only
                // when fully selected.
                if (
                    !selectedLink || (selectedLink && prevSelectedLink && (selectedLink !== prevSelectedLink)) ||
                    (!prevSelectedLink &&
                        selectionParent !== prevSelectionParent)
                ) {
                    this._hideUI();
                }
                // Update the position of the panel when:
                //  * the selection remains in the original link element,
                //  * there was no link element in the first place, i.e. creating a new link
                else {
                    // If still in a link element, simply update the position of the balloon.
                    // If there was no link (e.g. inserting one), the balloon must be moved
                    // to the new position in the editing view (a new native DOM range).
                    this._balloon.updatePosition(
                        this._getBalloonPositionData()
                    );
                }

                prevSelectedLink = selectedLink;
                prevSelectionParent = selectionParent;
            });

            function getSelectionParent() {
                return viewDocument.selection.focus
                    .getAncestors()
                    .reverse()
                    .find((node) => node.is('element'));
            }
        }

        /*
         * Returns true when actionsView is in the balloon.
         */
        get _areActionsInPanel(): boolean {
            return this._balloon.hasView(this.actionsView);
        }

        /*
         * Returns true when actionsView is in the balloon and it is curently visible.
         */
        get _areActionsVisible(): boolean {
            return this._balloon.visibleView === this.actionsView;
        }

        /*
         * Returns positioning options for the balloon. They control the way the balloon is attached
         * to the target element or selection.
         *
         * If the selection is collapsed and inside a link element, the panel will be attached to the
         * entire link element. Otherwise, it will be attached to the selection.
         */
        _getBalloonPositionData(): Object {
            const view = this.editor.editing.view;
            const viewDocument = view.document;
            const targetLink = this._getSelectedLinkElement();

            const target = targetLink
                ? // When selection is inside link element, then attach panel to this element.
                view.domConverter.mapViewToDom(targetLink)
                : // Otherwise attach panel to the selection.
                view.domConverter.viewRangeToDom(
                    viewDocument.selection.getFirstRange()
                );

            return {target};
        }

        /*
         * Returns the link AttributeElement under
         * the Document editing view's selection or `null`
         * if there is none.
         *
         * **Note**: For a non–collapsed selection the link element is only returned when **fully**
         * selected and the **only** element within the selection boundaries.
         */
        _getSelectedLinkElement(): AttributeElement {
            const selection = this.editor.editing.view.document.selection;

            if (selection.isCollapsed) {
                return findLinkElementAncestor(selection.getFirstPosition());
            } else {
                // The range for fully selected link is usually anchored in adjacent text nodes.
                // Trim it to get closer to the actual link element.
                const range = selection.getFirstRange().getTrimmed();
                const startLink = findLinkElementAncestor(range.start);
                const endLink = findLinkElementAncestor(range.end);

                if (!startLink || startLink != endLink) {
                    return null;
                }

                // Check if the link element is fully selected.
                if (
                    Range.createIn(startLink)
                        .getTrimmed()
                        .isEqual(range)
                ) {
                    return startLink;
                } else {
                    return null;
                }
            }
        }
    };

    /*
     * Returns a link element if there's one among the ancestors of the provided `Position`.
     */
    function findLinkElementAncestor(position: Position): AttributeElement {
        return position
            .getAncestors()
            .find((ancestor) => isLinkElement(ancestor));
    }
}
