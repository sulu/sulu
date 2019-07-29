// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, observable, when} from 'mobx';
import {Button, Loader} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaUpload from '../SingleMediaUpload';
import CropOverlay from './CropOverlay';
import FocusPointOverlay from './FocusPointOverlay';
import mediaDetailsStyles from './mediaVersionUpload.scss';

type Props = {|
    resourceStore: ResourceStore,
|};

@observer
class MediaVersionUpload extends React.Component<Props> {
    mediaUploadStore: MediaUploadStore;
    @observable showFocusPointOverlay: boolean = false;
    @observable showCropOverlay: boolean = false;
    showSuccess: IObservableValue<boolean> = observable.box(false);

    constructor(props: Props) {
        super(props);

        const {resourceStore} = this.props;
        const locale = resourceStore.locale;
        if (!locale) {
            throw new Error('The resourceStore for the MediaVersionUpload must have a locale');
        }

        when(
            () => !resourceStore.loading,
            (): void => {
                this.mediaUploadStore = new MediaUploadStore(resourceStore.data, locale);
            }
        );
    }

    handleUploadComplete = (media: Object) => {
        this.props.resourceStore.setMultiple(media);
    };

    @action handleCropButtonClick = () => {
        this.showCropOverlay = true;
    };

    @action handleCropOverlayClose = () => {
        this.showCropOverlay = false;
    };

    @action handleCropOverlayConfirm = () => {
        this.showCropOverlay = false;
        this.showSuccessSnackbar();
    };

    @action handleFocusPointButtonClick = () => {
        this.showFocusPointOverlay = true;
    };

    @action handleFocusPointOverlayClose = () => {
        this.showFocusPointOverlay = false;
    };

    @action handleFocusPointOverlayConfirm = () => {
        this.showFocusPointOverlay = false;
        this.showSuccessSnackbar();
    };

    @action showSuccessSnackbar() {
        this.showSuccess.set(true);
    }

    render() {
        if (!this.mediaUploadStore) {
            return (
                <Loader />
            );
        }

        const {id, locale} = this.props.resourceStore;
        if (!id) {
            return null;
        }

        if (!locale) {
            throw new Error('The "MediaVersionUpload" field type only works with a locale!');
        }

        return (
            <Fragment>
                <SingleMediaUpload
                    deletable={false}
                    downloadable={false}
                    imageSize="sulu-400x400-inset"
                    mediaUploadStore={this.mediaUploadStore}
                    onUploadComplete={this.handleUploadComplete}
                    uploadText={translate('sulu_media.upload_or_replace')}
                />
                <div className={mediaDetailsStyles.buttons}>
                    <Button
                        icon="su-focus"
                        onClick={this.handleFocusPointButtonClick}
                        skin="link"
                    >
                        {translate('sulu_media.set_focus_point')}
                    </Button>
                    <Button
                        icon="su-cut"
                        onClick={this.handleCropButtonClick}
                        skin="link"
                    >
                        {translate('sulu_media.crop')}
                    </Button>
                </div>
                <FocusPointOverlay
                    onClose={this.handleFocusPointOverlayClose}
                    onConfirm={this.handleFocusPointOverlayConfirm}
                    open={this.showFocusPointOverlay}
                    resourceStore={this.props.resourceStore}
                />
                <CropOverlay
                    id={id}
                    image={this.props.resourceStore.data.url}
                    locale={locale.get()}
                    onClose={this.handleCropOverlayClose}
                    onConfirm={this.handleCropOverlayConfirm}
                    open={this.showCropOverlay}
                />
            </Fragment>
        );
    }
}

export default MediaVersionUpload;
