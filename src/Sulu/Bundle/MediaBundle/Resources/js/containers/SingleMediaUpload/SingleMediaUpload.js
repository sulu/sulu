// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Dialog} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';
import singleMediaUploadStyles from './singleMediaUpload.scss';

type Props = {|
    collectionId?: number,
    deletable: boolean,
    downloadable: boolean,
    emptyIcon?: string,
    imageSize: string,
    mediaUploadStore: MediaUploadStore,
    onUploadComplete?: (media: Object) => void,
    skin: 'default' | 'round',
    uploadText: ?string,
|};

@observer
export default class SingleMediaUpload extends React.Component<Props> {
    static defaultProps = {
        deletable: true,
        downloadable: true,
        imageSize: 'sulu-400x400',
        skin: 'default',
    };

    @observable showDeleteDialog: boolean = false;
    @observable deleting: boolean = false;

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
        } else if(collectionId) {
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
        this.deleting = true;
        this.props.mediaUploadStore.delete()
            .then(action((media) => {
                this.callUploadComplete(media);
                this.deleting = false;
                this.showDeleteDialog = false;
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
            downloadable,
            deletable,
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
                    emptyIcon={emptyIcon}
                    image={mediaUploadStore.getThumbnail(imageSize)}
                    mimeType={mimeType}
                    onDrop={this.handleMediaDrop}
                    progress={progress}
                    skin={skin}
                    uploading={uploading}
                    uploadText={uploadText}
                />
                {mediaUploadStore.id &&
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
                    confirmLoading={this.deleting}
                    cancelText={translate('sulu_admin.cancel')}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleDeleteDialogCancelClick}
                    onConfirm={this.handleDeleteDialogConfirmClick}
                    open={this.showDeleteDialog}
                    title={translate('sulu_media.delete_media_warning_title')}
                >
                    {translate('sulu_media.delete_media_warning_text')}
                </Dialog>
            </Fragment>
        );
    }
}
