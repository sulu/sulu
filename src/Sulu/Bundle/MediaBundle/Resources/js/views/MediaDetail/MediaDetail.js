// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import {translate} from 'sulu-admin-bundle/utils';
import {Button} from 'sulu-admin-bundle/components';
import {Form, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import ImageFocusPointOverlay from './ImageFocusPointOverlay';
import mediaDetailStyles from './mediaDetail.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';
const THUMBNAIL_SIZE = 'sulu-400x400-inset';
const SET_FOCUS_POINT_ICON = 'crosshairs';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class MediaDetail extends React.PureComponent<Props> {
    mediaUploadStore: MediaUploadStore;
    form: ?Form;
    @observable imageFocusPointOverlayOpen: boolean = false;

    componentWillMount() {
        const {
            router,
            resourceStore,
        } = this.props;

        const locale = resourceStore.locale;

        if (!locale) {
            throw new Error('The resourceStore for the MediaDetail must have a locale');
        }

        router.bind('locale', locale);
        this.mediaUploadStore = new MediaUploadStore(locale);
    }

    @computed get thumbnail(): ?string {
        const {resourceStore} = this.props;
        const {
            data: {
                thumbnails,
            },
        } = resourceStore;

        if (!thumbnails || !thumbnails[THUMBNAIL_SIZE]) {
            return null;
        }

        return `${window.location.origin}${thumbnails[THUMBNAIL_SIZE]}`;
    }

    @computed get mimeType(): string {
        const {resourceStore} = this.props;
        return resourceStore.data.mimeType;
    }

    componentWillUnmount() {
        const {resourceStore, router} = this.props;

        if (resourceStore.locale) {
            router.unbind('locale', resourceStore.locale);
        }
    }

    setFormRef = (form) => {
        this.form = form;
    };

    @action openImageFocusPointOverlay() {
        this.imageFocusPointOverlayOpen = true;
    }

    @action closeImageFocusPointOverlay() {
        this.imageFocusPointOverlayOpen = false;
    }

    handleMediaDrop = (file: File) => {
        const {resourceStore} = this.props;
        const {
            data: {
                id: mediaId,
            },
        } = resourceStore;

        this.mediaUploadStore.update(mediaId, file)
            .then((data) => {
                for (const key of Object.keys(data)) {
                    resourceStore.set(key, data[key]);
                }
            });
    };

    handleSubmit = () => {
        this.props.resourceStore.save();
    };

    handleOpenImageFocusPointOverlay = () => {
        this.openImageFocusPointOverlay();
    };

    handleCloseImageFocusPointOverlay = () => {
        this.closeImageFocusPointOverlay();
    };

    render() {
        const {resourceStore} = this.props;
        const {
            progress,
            uploading,
        } = this.mediaUploadStore;

        return (
            <div className={mediaDetailStyles.mediaDetail}>
                <section className={mediaDetailStyles.previewContainer}>
                    <SingleMediaDropzone
                        image={this.thumbnail}
                        uploading={uploading}
                        progress={progress}
                        onDrop={this.handleMediaDrop}
                        uploadText={translate('sulu_media.upload_or_replace')}
                        mimeType={this.mimeType}
                    />
                    <Button
                        skin="link"
                        icon={SET_FOCUS_POINT_ICON}
                        onClick={this.handleOpenImageFocusPointOverlay}
                    >
                        {translate('sulu_media.set_focus_point')}
                    </Button>
                </section>
                <section className={mediaDetailStyles.formContainer}>
                    <Form
                        ref={this.setFormRef}
                        store={resourceStore}
                        onSubmit={this.handleSubmit}
                    />
                </section>
                <ImageFocusPointOverlay
                    resourceStore={resourceStore}
                    open={this.imageFocusPointOverlayOpen}
                    onClose={this.handleCloseImageFocusPointOverlay}
                />
            </div>
        );
    }
}

export default withToolbar(MediaDetail, function() {
    const {
        router,
        resourceStore,
    } = this.props;
    const {locales} = router.route.options;
    const locale = locales
        ? {
            value: resourceStore.locale.get(),
            onChange: (locale) => {
                resourceStore.setLocale(locale);
            },
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        locale,
        backButton: {
            onClick: () => {
                router.restore(COLLECTION_ROUTE, {locale: resourceStore.locale.get()});
            },
        },
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.save'),
                icon: 'floppy-o',
                disabled: !resourceStore.dirty,
                loading: resourceStore.saving,
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
