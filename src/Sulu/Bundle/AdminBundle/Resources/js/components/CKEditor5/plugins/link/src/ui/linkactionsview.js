// @flow
import View from '@ckeditor/ckeditor5-ui/src/view';
import ViewCollection from '@ckeditor/ckeditor5-ui/src/viewcollection';

import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

import FocusTracker from '@ckeditor/ckeditor5-utils/src/focustracker';
import FocusCycler from '@ckeditor/ckeditor5-ui/src/focuscycler';
import KeystrokeHandler from '@ckeditor/ckeditor5-utils/src/keystrokehandler';

import pencilIcon from '@ckeditor/ckeditor5-core/theme/icons/pencil.svg';
import unlinkIcon from '../../theme/icons/unlink.svg';
import {isSafeUrl} from '../utils';

export default function(tag: string, attributeName: string, validateURL: boolean = false) {
    /*
     * The link actions view class. This view displays createLinkPlugin preview, allows
     * unlinking or editing the link.
     */
    return class LinkActionsView extends View {
        /*
         * Tracks information about DOM focus in the actions.
         */
        focusTracker: FocusTracker;

        /*
         * An instance of the KeystrokeHandler.
         */
        keyStrokes: KeystrokeHandler;

        /*
         * The href preview view.
         */
        previewButtonView: View;

        /*
         * The unlink button view.
         */
        unlinkButtonView: ButtonView;

        /*
         * The edit link button view.
         */
        editButtonView: ButtonView;

        /*
         * A collection of views which can be focused in the view.
         */
        _focusables: ViewCollection;

        /*
         * Helps cycling over focusables in the view.
         */
        _focusCycler: FocusCycler;

        constructor(locale: any) {
            super(locale);

            this.focusTracker = new FocusTracker();
            this.keystrokes = new KeystrokeHandler();

            if (tag === 'a' && attributeName === 'href') {
                this.previewButtonView = this._createPreviewButton();
            }

            this.unlinkButtonView = this._createButton(
                'Unlink',
                unlinkIcon,
                'unlink'
            );

            this.editButtonView = this._createButton(
                'Edit link',
                pencilIcon,
                'edit'
            );

            this.set(attributeName);

            this._focusables = new ViewCollection();

            this._focusCycler = new FocusCycler({
                focusables: this._focusables,
                focusTracker: this.focusTracker,
                keystrokeHandler: this.keystrokes,
                actions: {
                    // Navigate fields backwards using the Shift + Tab keystroke.
                    focusPrevious: 'shift + tab',

                    // Navigate fields forwards using the Tab key.
                    focusNext: 'tab',
                },
            });

            const children = [];

            if (tag === 'a' && attributeName === 'href') {
                children.push(this.previewButtonView);
            }

            children.push(this.editButtonView);
            children.push(this.unlinkButtonView);

            this.setTemplate({
                tag: 'div',

                attributes: {
                    class: ['ck', 'ck-link-actions'],

                    // https://github.com/ckeditor/ckeditor5-link/issues/90
                    tabindex: '-1',
                },
                children,
            });
        }

        render() {
            super.render();

            const childViews = [];

            if (tag === 'a' && attributeName === 'href') {
                childViews.push(this.previewButtonView);
            }

            childViews.push(this.editButtonView);
            childViews.push(this.unlinkButtonView);

            childViews.forEach((v) => {
                // Register the view as focusable.
                this._focusables.add(v);

                // Register the view in the focus tracker.
                this.focusTracker.add(v.element);
            });

            // Start listening for the keystrokes coming from #element.
            this.keystrokes.listenTo(this.element);
        }

        /*
         * Focuses the fist focusables in the actions.
         */
        focus() {
            this._focusCycler.focusFirst();
        }

        /*
         * Creates a button view.
         */
        _createButton(label: string, icon: string, eventName: string): ButtonView {
            const button = new ButtonView(this.locale);

            button.set({
                label,
                icon,
                tooltip: true,
            });

            button.delegate('execute').to(this, eventName);

            return button;
        }

        /*
         * Creates a link href preview button.
         */
        _createPreviewButton(): ButtonView {
            const button = new ButtonView(this.locale);
            const bind = this.bindTemplate;

            button.set({
                withText: true,
                tooltip: 'Open link in new tab',
            });

            button.extendTemplate({
                attributes: {
                    class: ['ck', 'ck-link-actions__preview'],
                    href: bind.to('href', (value) => this._validateURL(value) ? value.value : '#'),
                    target: '_blank',
                },
            });

            button.bind('label').to(this, 'href', (value) => {
                if (!this._checkURL(value)) {
                    return 'This link has no URL';
                }

                if (!this._validateURL(value)) {
                    return 'URL is invalid';
                }

                return value.value;
            });

            button.bind('isEnabled').to(this, 'href', this._validateURL);

            button.template.tag = 'a';
            button.template.eventListeners = {};

            return button;
        }

        _checkURL = (value: Object): boolean => {
            if (!value || !value.value) {
                return false;
            }

            return true;
        };

        _validateURL = (value: Object): boolean => {
            if (!this._checkURL(value) || (validateURL && !isSafeUrl(value.value))) {
                return false;
            }

            return true;
        };
    };
}
