// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {Datagrid, DatagridStore, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import MediaCollection from '../../containers/MediaCollection';
import CollectionStore from '../../stores/CollectionStore';
import mediaOverviewStyles from './mediaOverview.scss';

const COLLECTION_ROUTE = 'sulu_media.overview';
const MEDIA_ROUTE = 'sulu_media.form.detail';

const COLLECTIONS_RESOURCE_KEY = 'collections';
const MEDIA_RESOURCE_KEY = 'media';

const USER_SETTINGS_KEY = 'media_overview';

@observer
class MediaOverview extends React.Component<ViewProps> {
    collectionPage: IObservableValue<number> = observable.box();
    mediaPage: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    collectionId: IObservableValue<?number | string> = observable.box();
    @observable mediaDatagridStore: DatagridStore;
    @observable collectionDatagridStore: DatagridStore;
    @observable collectionStore: CollectionStore;
    mediaDatagrid: ?ElementRef<typeof Datagrid>;
    disposer: () => void;

    static getDerivedRouteAttributes() {
        return {
            collectionLimit: DatagridStore.getLimitSetting(COLLECTIONS_RESOURCE_KEY, USER_SETTINGS_KEY),
            mediaLimit: DatagridStore.getLimitSetting(MEDIA_RESOURCE_KEY, USER_SETTINGS_KEY),
        };
    }

    constructor(props: ViewProps) {
        super(props);

        const {router} = this.props;

        this.mediaPage.set(1);

        router.bind('collectionPage', this.collectionPage, 1);
        router.bind('mediaPage', this.mediaPage, 1);
        router.bind('locale', this.locale);
        router.bind('id', this.collectionId);

        this.disposer = autorun(this.createCollectionStore);

        this.createCollectionDatagridStore();
        this.createMediaDatagridStore();

        router.bind('search', this.mediaDatagridStore.searchTerm);
        router.bind('collectionLimit', this.collectionDatagridStore.limit, 10);
        router.bind('mediaLimit', this.mediaDatagridStore.limit, 10);
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
            USER_SETTINGS_KEY,
            {
                page: this.collectionPage,
                locale: this.locale,
                parentId: this.collectionId,
            }
        );
    };

    createMediaDatagridStore() {
        const options = {};

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
            USER_SETTINGS_KEY,
            {
                page: this.mediaPage,
                locale: this.locale,
                collection: this.collectionId,
            },
            options
        );
    }

    clearDatagrids() {
        this.mediaDatagridStore.clear();
        this.mediaDatagridStore.clearSelection();
        this.collectionDatagridStore.clear();
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

    setMediaDatagridRef = (mediaDatagrid) => {
        this.mediaDatagrid = mediaDatagrid;
    };

    render() {
        return (
            <div className={mediaOverviewStyles.mediaOverview}>
                <MediaCollection
                    collectionDatagridStore={this.collectionDatagridStore}
                    collectionStore={this.collectionStore}
                    locale={this.locale}
                    mediaDatagridAdapters={['media_card_overview', 'table']}
                    mediaDatagridRef={this.setMediaDatagridRef}
                    mediaDatagridStore={this.mediaDatagridStore}
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
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'su-trash-alt',
                disabled: this.mediaDatagridStore.selectionIds.length === 0,
                loading: this.mediaDatagridStore.selectionDeleting,
                onClick: this.mediaDatagrid.requestSelectionDelete,
            },
        ],
    };
});
