// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, when} from 'mobx';
import {Button, Dialog, FileUploadButton} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaUpload from '../SingleMediaUpload';
import CropOverlay from './CropOverlay';
import FocusPointOverlay from './FocusPointOverlay';
import mediaVersionUploadStyles from './mediaVersionUpload.scss';

type Props = {|
    onSuccess: ?() => void,
    resourceStore: ResourceStore,
|};

@observer
class MediaVersionUpload extends React.Component<Props> {
    mediaUploadStore: MediaUploadStore;
    @observable showFocusPointOverlay: boolean = false;
    @observable showCropOverlay: boolean = false;
    @observable showDeletePreviewDialog: boolean = false;
    @observable deletingPreview: boolean = false;

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
        this.callSuccess();
    };

    handlePreviewUploadClick = (file: File) => {
        this.mediaUploadStore.updatePreviewImage(file).then(this.callSuccess);
    };

    @action handleDeletePreviewClick = () => {
        this.showDeletePreviewDialog = true;
    };

    @action handleDeletePreviewConfirm = () => {
        this.deletingPreview = true;
        this.mediaUploadStore.deletePreviewImage().then(action(() => {
            this.deletingPreview = false;
            this.showDeletePreviewDialog = false;
            this.callSuccess();
        }));
    };

    @action handleDeletePreviewCancel = () => {
        this.showDeletePreviewDialog = false;
    };

    callSuccess = () => {
        const {onSuccess} = this.props;
        if (onSuccess) {
            onSuccess();
        }
    };

    @action handleCropButtonClick = () => {
        this.showCropOverlay = true;
    };

    @action handleCropOverlayClose = () => {
        this.showCropOverlay = false;
    };

    @action handleCropOverlayConfirm = () => {
        this.showCropOverlay = false;
        this.callSuccess();
    };

    @action handleFocusPointButtonClick = () => {
        this.showFocusPointOverlay = true;
    };

    @action handleFocusPointOverlayClose = () => {
        this.showFocusPointOverlay = false;
    };

    @action handleFocusPointOverlayConfirm = () => {
        this.showFocusPointOverlay = false;
        this.callSuccess();
    };

    render() {
        if (!this.mediaUploadStore) {
            return null;
        }
        const {resourceStore} = this.props;

        const {
            data: {
                previewImageId,
                isImage,
                url,
            },
            id,
            locale,
        } = resourceStore;

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
                    uploadText={translate('sulu_media.upload_new_version')}
                />
                <div className={mediaVersionUploadStyles.buttons}>
                    {isImage &&
                        <Fragment>
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
                                {translate('sulu_media.define_crops')}
                            </Button>
                        </Fragment>
                    }
                    {!isImage &&
                        <Fragment>
                            <FileUploadButton
                                icon="su-image"
                                onUpload={this.handlePreviewUploadClick}
                                skin="link"
                            >
                                {translate('sulu_media.upload_preview_image')}
                            </FileUploadButton>
                            <Button
                                disabled={!previewImageId}
                                icon="su-trash-alt"
                                onClick={this.handleDeletePreviewClick}
                                skin="link"
                            >
                                {translate('sulu_media.delete_preview_image')}
                            </Button>
                        </Fragment>
                    }
                </div>
                <FocusPointOverlay
                    onClose={this.handleFocusPointOverlayClose}
                    onConfirm={this.handleFocusPointOverlayConfirm}
                    open={this.showFocusPointOverlay}
                    resourceStore={resourceStore}
                />
                <CropOverlay
                    id={id}
                    image={url}
                    locale={locale.get()}
                    onClose={this.handleCropOverlayClose}
                    onConfirm={this.handleCropOverlayConfirm}
                    open={this.showCropOverlay}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.deletingPreview}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeletePreviewCancel}
                    onConfirm={this.handleDeletePreviewConfirm}
                    open={this.showDeletePreviewDialog}
                    title={translate('sulu_media.delete_preview_image_warning_title')}
                >
                    {translate('sulu_media.delete_preview_image_warning_text')}
                </Dialog>
            </Fragment>
        );
    }
}

export default MediaVersionUpload;
