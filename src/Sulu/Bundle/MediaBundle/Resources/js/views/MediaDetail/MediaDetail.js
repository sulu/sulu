// @flow
import React from 'react';
import {action, observable, when} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Grid, Loader} from 'sulu-admin-bundle/components';
import {Form, FormStore, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaUpload from '../../containers/SingleMediaUpload';
import mediaDetailStyles from './mediaDetail.scss';
import FocusPointOverlay from './FocusPointOverlay';

const COLLECTION_ROUTE = 'sulu_media.overview';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class MediaDetail extends React.Component<Props> {
    mediaUploadStore: MediaUploadStore;
    form: ?Form;
    formStore: FormStore;
    @observable showFocusPointOverlay: boolean = false;
    showSuccess = observable.box(false);

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

        when(
            () => !resourceStore.loading,
            (): void => {
                this.mediaUploadStore = new MediaUploadStore(resourceStore.data, locale);
            }
        );
    }

    componentWillUnmount() {
        this.formStore.destroy();
    }

    setFormRef = (form) => {
        this.form = form;
    };

    @action showSuccessSnackbar() {
        this.showSuccess.set(true);
    }

    handleSubmit = () => {
        return this.props.resourceStore.save().then(action(() => {
            this.showSuccessSnackbar();
        }));
    };

    handleUploadComplete = (media: Object) => {
        this.props.resourceStore.setMultiple(media);
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

    render() {
        const {resourceStore} = this.props;

        return (
            <div className={mediaDetailStyles.mediaDetail}>
                {this.formStore.loading
                    ? <Loader />
                    : <Grid>
                        <Grid.Section className={mediaDetailStyles.imageSection} size={4}>
                            <Grid.Item>
                                <SingleMediaUpload
                                    deletable={false}
                                    downloadable={false}
                                    imageSize="sulu-400x400-inset"
                                    mediaUploadStore={this.mediaUploadStore}
                                    onUploadComplete={this.handleUploadComplete}
                                    uploadText={translate('sulu_media.upload_or_replace')}
                                />
                                <div className={mediaDetailStyles.buttons}>
                                    <Button
                                        icon="su-focus"
                                        onClick={this.handleFocusPointButtonClick}
                                        skin="link"
                                    >
                                        {translate('sulu_media.set_focus_point')}
                                    </Button>
                                </div>
                            </Grid.Item>
                        </Grid.Section>
                        <Grid.Section size={8}>
                            <Grid.Item className={mediaDetailStyles.form}>
                                <Form
                                    onSubmit={this.handleSubmit}
                                    ref={this.setFormRef}
                                    store={this.formStore}
                                />
                            </Grid.Item>
                        </Grid.Section>
                    </Grid>
                }
                <FocusPointOverlay
                    onClose={this.handleFocusPointOverlayClose}
                    onConfirm={this.handleFocusPointOverlayConfirm}
                    open={this.showFocusPointOverlay}
                    resourceStore={resourceStore}
                />
            </div>
        );
    }
}

export default withToolbar(MediaDetail, function() {
    const {showSuccess} = this;
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
        showSuccess,
    };
});
