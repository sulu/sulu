// @flow
import {action, autorun, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {TeaserItem} from '../types';

export default class TeaserStore {
    @observable teaserItemIds: Array<{type: string, id: number | string}> = [];
    @observable teaserItems: Array<TeaserItem> = [];
    @observable loading: boolean = false;
    teaserDisposer: () => void;

    constructor() {
        this.teaserDisposer = autorun(this.loadTeasers);
    }

    destroy() {
        this.teaserDisposer();
    }

    loadTeasers = () => {
        this.loading = true;
        ResourceRequester.getList(
            'teasers',
            {ids: this.teaserItemIds.map((teaserItemId) => teaserItemId.type + ';' + teaserItemId.id)}
        ).then(action((response) => {
            this.teaserItems.splice(0, this.teaserItems.length, ...response._embedded.teasers);
            this.loading = false;
        }));
    };

    add(type: string, id: number | string) {
        this.teaserItemIds.push({type, id});
    }

    findById(type: string, id: number | string) {
        return this.teaserItems.find((teaserItem) => teaserItem.type === type && teaserItem.id === id);
    }
}
