// @flow
import React from 'react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import SingleMediaSelection from 'sulu-media-bundle/containers/SingleMediaSelection';
import type {Value as MediaValue} from 'sulu-media-bundle/containers/SingleMediaSelection/types';
import {translate} from '../../../../utils/Translator';
import Dialog from '../../../Dialog';
import Form from '../../../Form';
import createLinkPlugin from './src/link';

type Props = {
    open: boolean,
    plugin: MediaLinkPlugin,
};

@observer
export class MediaLinkPluginComponent extends React.Component<Props> {
    @observable selectedMedia: MediaValue;
    @observable mediaError: string | typeof undefined;

    constructor(props: Props) {
        super(props);

        this.selectedMedia = {id: undefined, title: undefined};
        this.mediaError = undefined;
    }

    @action handleMediaChange = (value: MediaValue) => {
        this.selectedMedia = value;
        this.mediaError = this.selectedMedia.id ? undefined : 'Bitte Bild auswählen';
    };

    handleCancel = () => {
        this.props.plugin.handleChange(null);
    };

    @action handleConfirm = () => {
        const {id, title} = this.selectedMedia;
        const {handleChange} = this.props.plugin;

        if (!id) {
            this.handleCancel();
            return;
        }

        const result = {
            value: id.toString(),
            text: title,
            attributes: {},
        };

        handleChange(result);
    };

    @action componentDidUpdate(prevProps: Props) {
        const {open, plugin: {currentValue}} = this.props;

        if (!prevProps.open && open && currentValue) {
            this.selectedMedia = {id: parseInt(currentValue.value), title: undefined};
        }

        if (prevProps.open && !open) {
            this.selectedMedia = {id: undefined, title: undefined};
            this.mediaError = undefined;
        }
    }

    render() {
        const {open} = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!this.selectedMedia.id}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title="Internal Media Link"
            >
                <Form>
                    <Form.Field error={this.mediaError} label="Image" required={true}>
                        <SingleMediaSelection
                            locale={observable.box('en')}
                            onChange={this.handleMediaChange}
                            value={this.selectedMedia}
                        />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}

type MediaLinkValueType = ?{
    value: string,
    attributes: {},
};

export default class MediaLinkPlugin {
    @observable open: boolean = false;
    setValue: (value: MediaLinkValueType) => void = () => {};
    currentValue: MediaLinkValueType = null;

    @action handleChange = (value: MediaLinkValueType): void => {
        this.setValue(value);
        this.setValue = () => {};
        this.currentValue = null;
        this.open = false;
    };

    @action handleLinkCommand = (
        setValue: (value: MediaLinkValueType) => void,
        currentValue: MediaLinkValueType
    ): void => {
        this.currentValue = currentValue;
        this.setValue = setValue;
        this.open = true;
    };

    getPlugin = (): Plugin => {
        return createLinkPlugin(
            'Media Link',
            'media',
            'sulu:media',
            'id',
            'mediaLinkHref',
            this.handleLinkCommand,
            false
        );
    };
}
