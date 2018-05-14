// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {withToolbar, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaOverviewStyles from './mediaOverview.scss';

const MEDIA_ROUTE = 'sulu_media.form.detail';
const COLLECTION_ROUTE = 'sulu_media.overview';
const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

@observer
class MediaOverview extends React.Component<ViewProps> {
    mediaPage: IObservableValue<number> = observable.box();
    collectionPage: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    collectionId: IObservableValue<?number | string> = observable.box();
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    @observable collectionStore: CollectionStore;
    disposer: () => void;

    constructor(props: ViewProps) {
        super(props);

        const {router} = this.props;

        this.mediaPage.set(1);

        router.bind('collectionPage', this.collectionPage, 1);
        router.bind('locale', this.locale);
        router.bind('id', this.collectionId);

        this.disposer = autorun(this.createCollectionStore);
        this.createCollectionDatagridStore();
        this.createMediaDatagridStore();
    }

    componentWillUnmount() {
        this.mediaDatagridStore.destroy();
        this.collectionDatagridStore.destroy();
        this.collectionStore.destroy();
        this.disposer();
    }

    createCollectionStore = () => {
        this.setCollectionStore(new CollectionStore(this.collectionId.get(), this.locale));
    };

    @action setCollectionStore(collectionStore) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = collectionStore;
    }

    createCollectionDatagridStore = () => {
        this.collectionDatagridStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page: this.collectionPage,
                locale: this.locale,
                parent: this.collectionId,
            }
        );
    };

    createMediaDatagridStore() {
        const options = {};

        options.limit = 50;
        options.fields = [
            'id',
            'type',
            'name',
            'size',
            'title',
            'mimeType',
            'subVersion',
            'thumbnails',
        ].join(',');

        this.mediaDatagridStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page: this.mediaPage,
                locale: this.locale,
                collection: this.collectionId,
            },
            options
        );
    }

    clearDatagrids() {
        this.mediaDatagridStore.clearData();
        this.mediaDatagridStore.clearSelection();
        this.collectionDatagridStore.clearData();
        this.collectionDatagridStore.clearSelection();
    }

    @action handleCollectionNavigate = (collectionId) => {
        this.clearDatagrids();
        this.mediaPage.set(1);
        this.collectionPage.set(1);
        this.collectionId.set(collectionId);
    };

    handleMediaNavigate = (mediaId) => {
        const {router} = this.props;
        router.navigate(
            MEDIA_ROUTE,
            {
                id: mediaId,
                locale: this.locale.get(),
            }
        );
    };

    render() {
        return (
            <div className={mediaOverviewStyles.mediaOverview}>
                <MediaCollection
                    locale={this.locale}
                    collectionStore={this.collectionStore}
                    mediaDatagridAdapters={['media_card_overview', 'table']}
                    mediaDatagridStore={this.mediaDatagridStore}
                    collectionDatagridStore={this.collectionDatagridStore}
                    onCollectionNavigate={this.handleCollectionNavigate}
                    onMediaNavigate={this.handleMediaNavigate}
                />
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const router = this.props.router;
    const loading = this.collectionDatagridStore.loading || this.mediaDatagridStore.loading;

    const {
        route: {
            options: {
                locales,
            },
        },
    } = this.props.router;

    const locale = locales
        ? {
            value: this.locale.get(),
            onChange: action((locale) => {
                this.locale.set(locale);
            }),
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        locale,
        disableAll: loading,
        backButton: this.collectionId.get()
            ? {
                onClick: () => {
                    this.clearDatagrids();
                    router.restore(
                        COLLECTION_ROUTE,
                        {
                            id: this.collectionStore.parentId,
                            locale: this.locale.get(),
                            collectionPage: '1',
                        }
                    );
                },
            }
            : undefined,
        items: [],
    };
});
