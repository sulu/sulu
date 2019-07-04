// @flow
import React, {Fragment} from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import {Button} from 'sulu-admin-bundle/components';
import {action, observable, when} from 'mobx';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import Loader from 'sulu-admin-bundle/components/Loader';
import SingleMediaUpload from '../../SingleMediaUpload';
import MediaUploadStore from '../../../stores/MediaUploadStore';
import mediaDetailsStyles from '../../../views/MediaDetails/mediaDetails.scss';
import FocusPointOverlay from '../../../views/MediaDetails/FocusPointOverlay';
import CropOverlay from '../../../views/MediaDetails/CropOverlay';

@observer
class MediaVersionUpload extends React.Component<FieldTypeProps<void>> {
    mediaUploadStore: MediaUploadStore;
    resourceStore: ResourceStore;
    @observable showFocusPointOverlay: boolean = false;
    @observable showCropOverlay: boolean = false;
    showSuccess: IObservableValue<boolean> = observable.box(false);

    constructor(props: FieldTypeProps<void>) {
        super(props);

        const {formInspector, router} = this.props;

        // $FlowFixMe
        if (!formInspector.formStore.resourceStore){
            throw new Error();
        }

        this.resourceStore = (formInspector.formStore.resourceStore: ResourceStore);
        const locale = this.resourceStore.locale;
        if (!locale) {
            throw new Error('The resourceStore for the MediaVersionUpload must have a locale');
        }
        if (router){
            router.bind('locale', locale);
        }
        when(
            () => !this.resourceStore.loading,
            (): void => {
                this.mediaUploadStore = new MediaUploadStore(this.resourceStore.data, locale);
            }
        );
    }

    handleUploadComplete = (media: Object) => {
        this.resourceStore.setMultiple(media);
    };

    @action handleFocusPointButtonClick = () => {
        this.showFocusPointOverlay = true;
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
        if (!this.resourceStore) {
            return (
                <Loader />
            );
        }

        const {id, locale} = this.resourceStore;
        if (!id) {
            throw new Error('The "MediaVersionUpload" field type only works with an id!');
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
                    resourceStore={this.resourceStore}
                />
                <CropOverlay
                    id={id}
                    image={this.resourceStore.data.url}
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
