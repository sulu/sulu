// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable, when} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Grid, Loader} from 'sulu-admin-bundle/components';
import {Form, ResourceFormStore, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import MediaUploadStore from '../../stores/MediaUploadStore';
import SingleMediaUpload from '../../containers/SingleMediaUpload';
import mediaDetailsStyles from './mediaDetails.scss';
import FocusPointOverlay from './FocusPointOverlay';
import CropOverlay from './CropOverlay';

const COLLECTION_ROUTE = 'sulu_media.overview';
const FORM_KEY = 'media_details';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class MediaDetails extends React.Component<Props> {
    mediaUploadStore: MediaUploadStore;
    form: ?ElementRef<typeof Form>;
    formStore: ResourceFormStore;
    @observable showFocusPointOverlay: boolean = false;
    @observable showCropOverlay: boolean = false;
    showSuccess: IObservableValue<boolean> = observable.box(false);

    constructor(props: Props) {
        super(props);

        const {
            router,
            resourceStore,
        } = this.props;

        this.formStore = new ResourceFormStore(resourceStore, FORM_KEY);
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

    setFormRef = (form: ?ElementRef<typeof Form>) => {
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

    render() {
        const {resourceStore} = this.props;
        const {id, locale} = resourceStore;

        if (!id) {
            throw new Error('The "MediaDetails" view only works with an id!');
        }

        if (!locale) {
            throw new Error('The "MediaDetails" view only works with an locale!');
        }

        return (
            <div className={mediaDetailsStyles.mediaDetail}>
                {this.formStore.loading
                    ? <Loader />
                    : <Grid>
                        <Grid.Section className={mediaDetailsStyles.imageSection} colSpan={4}>
                            <Grid.Item>
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
                            </Grid.Item>
                        </Grid.Section>
                        <Grid.Section colSpan={8}>
                            <Grid.Item className={mediaDetailsStyles.form}>
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
                <CropOverlay
                    id={id}
                    image={resourceStore.data.url}
                    locale={locale.get()}
                    onClose={this.handleCropOverlayClose}
                    onConfirm={this.handleCropOverlayConfirm}
                    open={this.showCropOverlay}
                />
            </div>
        );
    }
}

export default withToolbar(MediaDetails, function() {
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
                disabled: !resourceStore.dirty,
                icon: 'su-save',
                label: translate('sulu_admin.save'),
                loading: resourceStore.saving,
                onClick: () => {
                    this.form.submit();
                },
                type: 'button',
            },
        ],
        showSuccess,
    };
});
