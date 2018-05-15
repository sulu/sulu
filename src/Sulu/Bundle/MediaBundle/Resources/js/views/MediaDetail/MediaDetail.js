// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import {translate} from 'sulu-admin-bundle/utils';
import {Form, FormStore, withToolbar} from 'sulu-admin-bundle/containers';
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
class MediaDetail extends React.Component<Props> {
    mediaUploadStore: MediaUploadStore;
    form: ?Form;
    formStore: FormStore;

    constructor(props: Props) {
        super(props);

        const {
            router,
            resourceStore,
        } = this.props;

        this.formStore = new FormStore(resourceStore);
        const locale = resourceStore.locale;

        if (!locale) {
            throw new Error('The resourceStore for the MediaDetail must have a locale');
        }

        router.bind('locale', locale);
        this.mediaUploadStore = new MediaUploadStore(locale);
    }

    componentWillUnmount() {
        this.formStore.destroy();
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

    setFormRef = (form) => {
        this.form = form;
    };

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

    render() {
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
                </section>
                <section className={mediaDetailStyles.formContainer}>
                    <Form
                        ref={this.setFormRef}
                        store={this.formStore}
                        onSubmit={this.handleSubmit}
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
                icon: 'su-save',
                disabled: !resourceStore.dirty,
                loading: resourceStore.saving,
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
