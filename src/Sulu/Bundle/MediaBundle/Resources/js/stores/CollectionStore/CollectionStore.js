// @flow
import {action, autorun, computed, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {BreadcrumbItem, BreadcrumbItems, Collection} from './types';

const COLLECTIONS_RESOURCE_KEY = 'collections';

export default class CollectionStore {
    @observable loading: boolean = false;
    @observable collection: Collection = {
        parentId: null,
        breadcrumb: null,
    };
    disposer: () => void;

    constructor(collectionId: ?number, locale: observable) {
        this.disposer = autorun(() => {
            this.load(collectionId, locale.get());
        });
    }

    destroy() {
        this.disposer();
    }

    @computed get parentId(): ?number {
        return this.collection.parentId;
    }

    @computed get breadcrumb(): ?BreadcrumbItems {
        return this.collection.breadcrumb;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action load(collectionId: ?number, locale: string) {
        if (!collectionId) {
            this.collection.breadcrumb = null;

            return;
        }

        this.setLoading(true);

        return ResourceRequester.get(
            COLLECTIONS_RESOURCE_KEY,
            collectionId,
            {
                depth: 1,
                locale: locale,
                breadcrumb: true,
            }
        ).then((collectionInfo) => {
            const {
                _embedded: {
                    parent,
                    breadcrumb,
                },
            } = collectionInfo;
            const currentCollection = this.getCurrentCollectionItem(collectionInfo);

            this.collection.parentId = (parent) ? parent.id : null;
            this.collection.breadcrumb = (breadcrumb) ? [...breadcrumb, currentCollection] : [currentCollection];

            this.setLoading(false);
        });
    }

    getCurrentCollectionItem(data: Object): BreadcrumbItem {
        return {
            id: data.id,
            title: data.title,
        };
    }
}
