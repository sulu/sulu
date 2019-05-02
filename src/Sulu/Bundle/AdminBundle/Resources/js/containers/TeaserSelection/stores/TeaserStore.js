// @flow
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {TeaserItem} from '../types';

export default class TeaserStore {
    locale: IObservableValue<string>;
    @observable teaserItemIds: Array<{type: string, id: number | string}> = [];
    @observable teaserItems: Array<TeaserItem> = [];
    @observable loading: boolean = false;
    teaserDisposer: () => void;

    constructor(locale: IObservableValue<string>) {
        this.locale = locale;
        this.teaserDisposer = autorun(this.loadTeasers);
    }

    destroy() {
        this.teaserDisposer();
    }

    loadTeasers = () => {
        this.setLoading(true);
        ResourceRequester.getList(
            'teasers',
            {
                ids: this.teaserItemIds.map((teaserItemId) => teaserItemId.type + ';' + teaserItemId.id),
                locale: this.locale.get(),
            }
        ).then(action((response) => {
            this.teaserItems.splice(0, this.teaserItems.length, ...response._embedded.teasers);
            this.setLoading(false);
        }));
    };

    add(type: string, id: number | string) {
        if (this.teaserItemIds.find((teaserItemId) => teaserItemId.type === type && teaserItemId.id === id)) {
            return;
        }

        this.teaserItemIds.push({type, id});
    }

    findById(type: string, id: number | string) {
        return this.teaserItems.find((teaserItem) => teaserItem.type === type && teaserItem.id === id);
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }
}
