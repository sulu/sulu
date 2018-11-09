// @flow
import * as React from 'react';
import {observable, autorun, computed, action} from 'mobx';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import mediaLinkIcon from '@ckeditor/ckeditor5-core/theme/icons/image.svg';
import Dialog from '../Dialog/Dialog';

const PLUGIN_NAME = 'ckeditor-media-link';

type MediaLinkPluginComponentProps = {
    open: boolean,
    onClose: () => void,
    onConfirm: () => void,
};

export class MediaLinkPluginComponent extends React.Component<MediaLinkPluginComponentProps> {
    static defaultProps = {
        open: false,
    };

    render() {
        const {onClose, onConfirm, open} = this.props;

        return (
            <Dialog
                cancelText="Cancel"
                confirmText="Confirm"
                onCancel={onClose}
                onConfirm={onConfirm}
                open={open}
                title="Dialog"
            >
                Hello world!
            </Dialog>
        );
    }

    static getPluginName() {
        return PLUGIN_NAME;
    }
}
export default function(
    updateComponentState: (key: string, state: any) => void
) {
    return class MediaLinkPlugin extends Plugin {
        @observable componentOpen = false;

        @computed get componentProps() {
            return {
                open: this.componentOpen,
                onConfirm: this.onConfirm,
                onClose: this.onClose,
            };
        }

        @action onConfirm = () => {
            this.componentOpen = false;
        };

        @action onClose = () => {
            this.componentOpen = false;
        };

        init() {
            const editor = this.editor;

            updateComponentState(PLUGIN_NAME, this.componentProps);

            autorun(() => {
                updateComponentState(PLUGIN_NAME, this.componentProps);
            });

            editor.ui.componentFactory.add('mediaLink', (locale) => {
                const view = new ButtonView(locale);

                view.set({
                    label: 'Insert media link',
                    icon: mediaLinkIcon,
                    tooltip: true,
                });

                view.on('execute', action(() => {
                    this.componentOpen = true;
                }));

                return view;
            });
        }
    };
}
