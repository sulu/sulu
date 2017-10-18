// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/services';
import {withToolbar, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {CollectionInfoStore, MediaContainer} from '../../containers/MediaContainer';

const COLLECTION_ROUTE = 'sulu_media.overview';
const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

@observer
class MediaOverview extends React.PureComponent<ViewProps> {
    mediaPage: observable = observable();
    collectionPage: observable = observable();
    locale: observable = observable();
    @observable collectionId: ?string | number;
    @observable mediaStore: DatagridStore;
    @observable collectionStore: DatagridStore;
    collectionInfoStore: CollectionInfoStore;
    disposer: () => void;

    componentWillMount() {
        const {router} = this.props;

        this.mediaPage.set(1);

        router.bindQuery('collectionPage', this.collectionPage, '1');
        router.bindQuery('locale', this.locale);

        this.disposer = autorun(this.createStores);
    }

    componentWillUnmount() {
        const {router} = this.props;

        this.disposer();
        router.unbind('locale', this.page);
        router.unbind('page', this.locale);
        this.mediaStore.destroy();
        this.collectionStore.destroy();
        this.collectionInfoStore.destroy();
    }

    getCollectionId() {
        const {router} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;

        return id;
    }

    createStores = () => {
        const collectionId = this.getCollectionId();

        if (collectionId !== this.collectionId || !this.collectionStore) {
            this.setCollectionId(collectionId);
            this.createMediaStore(collectionId, this.mediaPage, this.locale);
            this.createCollectionStore(collectionId, this.collectionPage, this.locale);
            this.createCollectionInfoStore(collectionId, this.locale);
        }
    };

    @action setCollectionId(id) {
        this.collectionId = id;
    }

    @action createCollectionStore(collectionId, page, locale) {
        if (this.collectionStore) {
            this.collectionStore.destroy();
        }

        this.collectionStore = new DatagridStore(
            COLLECTIONS_RESOURCE_KEY,
            {
                page,
                locale,
            },
            (collectionId) ? {parent: collectionId} : undefined
        );
    }

    createCollectionInfoStore = (collectionId, locale) => {
        if (this.collectionInfoStore) {
            this.collectionInfoStore.destroy();
        }

        this.collectionInfoStore = new CollectionInfoStore(collectionId, locale);
    };

    @action createMediaStore(collectionId, page, locale) {
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

        if (collectionId) {
            options.collection = collectionId;
        }

        if (this.mediaStore) {
            this.mediaStore.destroy();
        }

        this.mediaStore = new DatagridStore(
            MEDIA_RESOURCE_KEY,
            {
                page,
                locale,
            },
            options
        );
    }

    handleCollectionOpen = (collectionId) => {
        const {router} = this.props;
        router.navigate(COLLECTION_ROUTE, {id: collectionId, locale: this.locale.get()});
    };

    render() {
        return (
            <div>
                <MediaContainer
                    page={this.collectionPage}
                    locale={this.locale}
                    mediaView="mediaCardOverview"
                    mediaStore={this.mediaStore}
                    collectionStore={this.collectionStore}
                    collectionInfoStore={this.collectionInfoStore}
                    onCollectionNavigate={this.handleCollectionOpen}
                />
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const router = this.props.router;
    const {parentCollectionId} = this.collectionInfoStore;
    const loading = this.collectionStore.loading || this.mediaStore.loading;

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
        backButton: (this.collectionId)
            ? {
                onClick: () => {
                    router.restore(
                        COLLECTION_ROUTE,
                        {
                            id: this.parentId,
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
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                onClick: () => {},
            },
        ],
    };
});
