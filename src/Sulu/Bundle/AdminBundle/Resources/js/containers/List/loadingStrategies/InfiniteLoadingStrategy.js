// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import RequestPromise from '../../../services/Requester/RequestPromise';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';
import type {LoadOptions} from '../types';

const LIMIT = 50;

export default class InfiniteLoadingStrategy extends AbstractLoadingStrategy {
    lastLoadedPage: number = 0;

    load(resourceKey: string, options: LoadOptions, parentId: ?string | number) {
        let previousPagesItemsPromise = new RequestPromise((resolve) => resolve(undefined));
        previousPagesItemsPromise.setAbortController(new AbortController());

        // make sure that list contains (only) items of previous pages if given page does not match the expected page:
        // - prevent missing items if the application is refreshed with a query parameter like  "?mediaPage=4"
        // - prevent duplicated items if the current page is reloaded (eg. after an item was deleted)
        if (options.page && options.page - 1 !== this.lastLoadedPage) {
            if (options.page === 1) {
                previousPagesItemsPromise = new RequestPromise((resolve) => resolve([]));
                previousPagesItemsPromise.setAbortController(new AbortController());
            } else {
                previousPagesItemsPromise = ResourceRequester.getList(
                    resourceKey, {...options, page: 1, limit: (options.page - 1) * LIMIT}
                ).then((previousPagesResponse) => previousPagesResponse._embedded[resourceKey]);
            }
        }

        return previousPagesItemsPromise.then((previousPagesItems) => {
            return ResourceRequester.getList(resourceKey, {...options, limit: LIMIT}).then((response) => {
                return [previousPagesItems, response];
            });
        }).then(action(([previousPagesItems, currentPageResponse]) => {
            if (previousPagesItems) {
                this.structureStrategy.clear();
                previousPagesItems.forEach((item) => this.structureStrategy.addItem(item, parentId));
            }

            const currentPageItems = currentPageResponse._embedded[resourceKey];
            currentPageItems.forEach((item) => this.structureStrategy.addItem(item, parentId));

            if (options.page) {
                this.lastLoadedPage = options.page;
            }

            return currentPageResponse;
        }));
    }
}
