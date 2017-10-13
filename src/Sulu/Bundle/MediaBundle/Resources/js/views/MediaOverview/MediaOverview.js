// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate, ResourceRequester} from 'sulu-admin-bundle/services';
import {withToolbar, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import MediaContainer from '../../containers/MediaContainer';

const COLLECTION_ROUTE = 'sulu_media.overview';
const MEDIA_RESOURCE_KEY = 'media';
const COLLECTIONS_RESOURCE_KEY = 'collections';

@observer
class MediaOverview extends React.PureComponent<ViewProps> {
    page: observable = observable();
    locale: observable = observable();
    @observable collectionId: ?string | number;
    @observable parentCollectionId: ?string | number;
    @observable mediaStore: DatagridStore;
    @observable collectionStore: DatagridStore;
    datagridDisposer: () => void;
    collectionInfoDisposer: () => void;

    componentWillMount() {
        const {router} = this.props;

        router.bind('page', this.page, '1');
        router.bind('locale', this.locale);

        this.datagridDisposer = autorun(this.createDatagridStores);
        this.collectionInfoDisposer = autorun(this.createCollectionInfo);
    }

    componentWillUnmount() {
        const {router} = this.props;

        this.disposer();
        router.unbind('locale', this.page);
        router.unbind('page', this.locale);
        this.collectionStore.destroy();
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

    createCollectionInfo = () => {
        const collectionId = this.getCollectionId();

        if (!collectionId) {
            return;
        }

        this.loadCollectionInfo(collectionId, this.locale)
            .then((collectionInfo) => {
                const {
                    _embedded: {
                        parent,
                    },
                } = collectionInfo;

                this.setParentCollectionId((parent) ? parent.id : null);
            });
    };

    createDatagridStores = () => {
        const collectionId = this.getCollectionId();

        if (collectionId !== this.collectionId || !this.collectionStore) {
            this.setCollectionId(collectionId);
            this.createMediaStore(collectionId, this.page, this.locale);
            this.createCollectionStore(collectionId, this.page, this.locale);
        }
    };

    loadCollectionInfo(collectionId, locale) {
        // TODO: Using "ResourceRequester" inside the view is iffy.
        // Discuss if the following code should be moved into an own file (maybe a store?)
        return ResourceRequester.get(
            COLLECTIONS_RESOURCE_KEY,
            collectionId,
            {
                depth: 1,
                locale,
            }
        );
    }

    @action setCollectionId(id) {
        this.collectionId = id;
    }

    @action setParentCollectionId(id) {
        this.parentCollectionId = id;
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
                <h1>Eins Title</h1>
                <MediaContainer
                    page={this.page}
                    locale={this.locale}
                    mediaView="mediaCardOverview"
                    mediaStore={this.mediaStore}
                    collectionStore={this.collectionStore}
                    onCollectionOpen={this.handleCollectionOpen}
                />
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const router = this.props.router;

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
        disableAll: this.collectionStore.loading,
        backButton: (this.collectionId)
            ? {
                onClick: () => {
                    router.restore(COLLECTION_ROUTE, {id: this.parentId, locale: this.locale.get()});
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
