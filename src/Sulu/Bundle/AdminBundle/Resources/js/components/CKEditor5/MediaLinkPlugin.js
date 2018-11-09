// @flow
import * as React from 'react';
import {observer} from 'mobx-react';
import {observable, autorun} from 'mobx';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import mediaLinkIcon from '@ckeditor/ckeditor5-core/theme/icons/image.svg';
import Overlay from 'sulu-admin-bundle/containers/Overlay';

export default (
    renderComponent: (component, props) => void
) => {
    const MyComponent = (props) => {
        return (
            <div>
                <Overlay
                    confirmText="Auswählen"
                    onClose={props.onClose}
                    onConfirm={props.onConfirm}
                    open={props.open}
                    title="Bilder auswählen"
                >
                    Hello World!
                </Overlay>
            </div>
        )
    }

    return class MediaLinlPlugin extends Plugin {
        @observable componentProps = {
            open: false,
            onConfirm: this.onConfirm,
            onClose: this.onClose,
        };

        onConfirm = () => {

        }

        onClose = () => {
            
        }

        init() {
            const editor = this.editor;
            renderComponent(MyComponent);

            editor.ui.componentFactory.add('mediaLink', (locale) => {
                const view = new ButtonView(locale);

                view.set({
                    label: 'Insert media link',
                    icon: mediaLinkIcon,
                    tooltip: true,
                });

                view.on('execute', () => {
                    console.log("test");
                    renderComponent(MyComponent);
                });

                return view;
            });
        }
    };
}
