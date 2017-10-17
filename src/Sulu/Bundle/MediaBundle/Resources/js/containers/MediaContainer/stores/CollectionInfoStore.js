// @flow
import {action, autorun, observable} from 'mobx';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {BreadcrumbItem, BreadcrumbItems} from '../types';

const COLLECTIONS_RESOURCE_KEY = 'collections';

export default class CollectionInfoStore {
    @observable breadcrumb: ?observable = [];
    @observable parentCollectionId: ?observable;
    disposer: () => void;

    constructor(collectionId: ?string | number, locale: string) {
        this.disposer = autorun(() => {
            this.load(collectionId, locale);
        });
    }

    destroy() {
        this.disposer();
    }

    @action setParentCollectionId(id: ?string | number) {
        this.parentCollectionId = id;
    }

    @action setBreadcrumb(breadcrumb: ?BreadcrumbItems = []) {
        this.breadcrumb = breadcrumb;
    }

    load(collectionId: ?string | number, locale: string) {
        if (!collectionId) {
            this.setBreadcrumb(null);

            return;
        }

        return ResourceRequester.get(
            COLLECTIONS_RESOURCE_KEY,
            collectionId,
            {
                depth: 1,
                locale,
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

            this.setBreadcrumb(
                (breadcrumb)
                    ? [...breadcrumb, currentCollection]
                    : [currentCollection]
            );
            this.setParentCollectionId((parent) ? parent.id : null);
        });
    }

    getCurrentCollectionItem(data: Object): BreadcrumbItem {
        return {
            id: data.id,
            title: data.title,
        };
    }

    prepareBreadcrumbData(data: ?Array<Object>): ?BreadcrumbItems {
        if (data && data.length) {
            return data.map((entry) => {
                return {
                    id: entry.id,
                    title: entry.title,
                };
            });
        }

        return null;
    }
}
