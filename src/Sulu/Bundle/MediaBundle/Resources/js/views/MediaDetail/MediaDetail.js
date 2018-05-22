// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {Grid} from 'sulu-admin-bundle/components';
import {Form, FormStore, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaUpload from '../../containers/SingleMediaUpload';
import mediaDetailStyles from './mediaDetail.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';

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
        this.mediaUploadStore = new MediaUploadStore(resourceStore);
    }

    componentWillUnmount() {
        this.formStore.destroy();
    }

    setFormRef = (form) => {
        this.form = form;
    };

    handleSubmit = () => {
        this.props.resourceStore.save();
    };

    render() {
        return (
            <Grid className={mediaDetailStyles.mediaDetail}>
                <Grid.Section size={4}>
                    <Grid.Item>
                        <SingleMediaUpload
                            deletable={false}
                            downloadable={false}
                            imageSize="sulu-400x400-inset"
                            mediaUploadStore={this.mediaUploadStore}
                            uploadText={translate('sulu_media.upload_or_replace')}
                        />
                    </Grid.Item>
                </Grid.Section>
                <Grid.Section size={8}>
                    <Grid.Item>
                        <Form
                            onSubmit={this.handleSubmit}
                            ref={this.setFormRef}
                            store={this.formStore}
                        />
                    </Grid.Item>
                </Grid.Section>
            </Grid>
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
