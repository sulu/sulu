// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {arrayMove} from '../../components';
import {ResourceRequester} from '../../services';

export default class MultiSelectionStore {
    @observable items: Array<Object> = [];
    @observable loading: boolean = false;
    resourceKey: string;
    locale: ?IObservableValue<string>;
    idFilterParameter: string;

    constructor(
        resourceKey: string,
        selectedItemIds: Array<string | number>,
        locale: ?IObservableValue<string>,
        idFilterParameter: string = 'ids'
    ) {
        this.resourceKey = resourceKey;
        this.locale = locale;
        this.idFilterParameter = idFilterParameter;
        if (selectedItemIds.length) {
            this.loadItems(selectedItemIds);
        }
    }

    @action set(items: Array<Object>) {
        this.items = items;
    }

    @action removeById(id: string | number) {
        // TODO use metadata instead of hardcoded id
        this.items.splice(this.items.findIndex((item) => item.id === id), 1);
    }

    @action move(oldItemIndex: number, newItemIndex: number) {
        this.items = arrayMove(this.items, oldItemIndex, newItemIndex);
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action loadItems = (itemIds: ?Array<string | number>) => {
        if (!itemIds || itemIds.length === 0) {
            this.items = [];
            return;
        }

        this.setLoading(true);
        return ResourceRequester.getList(this.resourceKey, {
            locale: this.locale ? this.locale.get() : undefined,
            [this.idFilterParameter]: itemIds.join(','),
            limit: undefined,
            page: 1,
        }).then(action((data) => {
            this.items = data._embedded[this.resourceKey];
            this.setLoading(false);
        }));
    };
}
