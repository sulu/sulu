// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import {translate} from 'sulu-admin-bundle/utils';
import {withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import mediaDetailStyles from './mediaDetail.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';
const THUMBNAIL_SIZE = 'sulu-400x400-inset';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class MediaDetail extends React.PureComponent<Props> {
    mediaUploadStore: MediaUploadStore;

    componentWillMount() {
        const {
            router,
            resourceStore,
        } = this.props;

        if (!resourceStore.locale) {
            throw new Error('The resourceStore for the MediaDetail must have a locale');
        }

        router.bind('locale', resourceStore.locale);
        this.mediaUploadStore = new MediaUploadStore(resourceStore.locale);
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

    render() {
        const {
            progress,
            uploading,
        } = this.mediaUploadStore;

        return (
            <div className={mediaDetailStyles.mediaDetail}>
                <section>
                    <SingleMediaDropzone
                        image={this.thumbnail}
                        uploading={uploading}
                        progress={progress}
                        onDrop={this.handleMediaDrop}
                        uploadText={translate('sulu_media.upload_or_replace')}
                        mimeType={this.mimeType}
                    />
                </section>
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
                    resourceStore.save();
                },
            },
        ],
    };
});
