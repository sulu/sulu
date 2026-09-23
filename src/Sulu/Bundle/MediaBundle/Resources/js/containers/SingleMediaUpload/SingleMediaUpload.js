// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Dialog} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {ERROR_CODE_REFERENCING_RESOURCES_FOUND} from 'sulu-admin-bundle/constants';
import DeleteReferencedResourceDialog from 'sulu-admin-bundle/containers/DeleteReferencedResourceDialog';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';
import singleMediaUploadStyles from './singleMediaUpload.scss';
import type {ReferencingResourcesData} from 'sulu-admin-bundle/types';

type Props = {|
    collectionId?: number,
    deletable: boolean,
    disabled: boolean,
    downloadable: boolean,
    emptyIcon?: string,
    imageSize: string,
    mediaUploadStore: MediaUploadStore,
    onUploadComplete?: (media: Object) => void,
    skin: 'default' | 'round',
    uploadText: ?string,
|};

@observer
class SingleMediaUpload extends React.Component<Props> {
    static defaultProps = {
        deletable: true,
        disabled: false,
        downloadable: true,
        imageSize: 'sulu-400x400',
        skin: 'default',
    };

    @observable showDeleteDialog: boolean = false;
    @observable deleting: boolean = false;
    @observable referencingResourcesData: ?ReferencingResourcesData = undefined;

    @computed get errorMessage(): ?string {
        const error = this.props.mediaUploadStore.error;

        if (!error) {
            return undefined;
        }

        return error.detail || error.title || translate('sulu_media.upload_server_error');
    }

    constructor(props: Props) {
        super(props);

        const {
            collectionId,
            mediaUploadStore,
        } = this.props;

        if (!mediaUploadStore.media && !collectionId) {
            throw new Error('If a new item is supposed to be uploaded a "collectionId" is required!');
        }
    }

    handleMediaDrop = (file: File) => {
        const {
            collectionId,
            mediaUploadStore,
        } = this.props;

        if (mediaUploadStore.id) {
            mediaUploadStore.update(file)
                .then(this.callUploadComplete);
        } else if (collectionId) {
            mediaUploadStore.create(collectionId, file)
                .then(this.callUploadComplete);
        }
    };

    @action handleDownloadMediaClick = () => {
        window.location.assign(this.props.mediaUploadStore.downloadUrl);
    };

    @action handleDeleteMediaClick = () => {
        this.showDeleteDialog = true;
    };

    @action handleDeleteDialogCancelClick = () => {
        this.showDeleteDialog = false;
    };

    @action handleDeleteDialogConfirmClick = () => {
        this.delete();
    };

    @action handleReferencedResourceDialogCancel = () => {
        this.referencingResourcesData = undefined;
    };

    @action handleReferencedResourceDialogConfirm = () => {
        this.delete({force: true});
    };

    @action delete = (options: {force?: boolean} = {}) => {
        this.deleting = true;
        this.props.mediaUploadStore.delete(options)
            .then(action((media) => {
                this.callUploadComplete(media);
                this.deleting = false;
                this.showDeleteDialog = false;
                this.referencingResourcesData = undefined;
            }))
            .catch(action((error) => {
                this.deleting = false;
                this.showDeleteDialog = false;
                this.referencingResourcesData = undefined;

                if (error.status !== 409) {
                    // the error is shown by the dropzone using the error of the store
                    return;
                }

                return error.json().then(action((data) => {
                    if (data.code !== ERROR_CODE_REFERENCING_RESOURCES_FOUND) {
                        return;
                    }

                    this.referencingResourcesData = {
                        resource: data.resource,
                        referencingResources: data.referencingResources,
                        referencingResourcesCount: data.referencingResourcesCount,
                    };
                }));
            }));
    };

    callUploadComplete = (media: Object) => {
        const {onUploadComplete} = this.props;

        if (onUploadComplete) {
            onUploadComplete(media);
        }
    };

    render() {
        const {
            deletable,
            disabled,
            downloadable,
            emptyIcon,
            mediaUploadStore,
            imageSize,
            skin,
            uploadText,
        } = this.props;

        const {
            mimeType,
            progress,
            uploading,
        } = mediaUploadStore;

        return (
            <Fragment>
                <SingleMediaDropzone
                    disabled={disabled}
                    emptyIcon={emptyIcon}
                    errorText={this.errorMessage}
                    image={mediaUploadStore.getThumbnail(imageSize)}
                    mimeType={mimeType}
                    onDrop={this.handleMediaDrop}
                    progress={progress}
                    skin={skin}
                    uploading={uploading}
                    uploadText={uploadText}
                />
                {mediaUploadStore.id && !disabled &&
                    <div className={singleMediaUploadStyles.buttons}>
                        {downloadable &&
                            <Button
                                icon="su-download"
                                onClick={this.handleDownloadMediaClick}
                                skin="link"
                            >
                                {translate('sulu_media.download_media')}
                            </Button>
                        }
                        {deletable &&
                            <Button
                                icon="su-trash-alt"
                                onClick={this.handleDeleteMediaClick}
                                skin="link"
                            >
                                {translate('sulu_media.delete_media')}
                            </Button>
                        }
                    </div>
                }
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.deleting}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeleteDialogCancelClick}
                    onConfirm={this.handleDeleteDialogConfirmClick}
                    open={this.showDeleteDialog}
                    title={translate('sulu_media.delete_media_warning_title')}
                >
                    {translate('sulu_media.delete_media_warning_text')}
                </Dialog>
                {this.referencingResourcesData &&
                    <DeleteReferencedResourceDialog
                        confirmLoading={this.deleting}
                        onCancel={this.handleReferencedResourceDialogCancel}
                        onConfirm={this.handleReferencedResourceDialogConfirm}
                        referencingResourcesData={this.referencingResourcesData}
                    />
                }
            </Fragment>
        );
    }
}

export default SingleMediaUpload;
