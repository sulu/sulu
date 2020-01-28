// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {arrayMove} from '../../utils';
import {ResourceRequester} from '../../services';

export default class MultiSelectionStore<T = string | number, U: {id: T} = Object> {
    @observable items: Array<U> = [];
    @observable loading: boolean = false;
    resourceKey: string;
    locale: ?IObservableValue<string>;
    idFilterParameter: string;
    requestParameters: {[string]: any};

    constructor(
        resourceKey: string,
        selectedItemIds: Array<T>,
        locale: ?IObservableValue<string>,
        idFilterParameter: string = 'ids',
        requestParameters: {[string]: any} = {}
    ) {
        this.resourceKey = resourceKey;
        this.locale = locale;
        this.idFilterParameter = idFilterParameter;
        this.requestParameters = requestParameters;

        this.loadItems(selectedItemIds);
    }

    @action set(items: Array<U>) {
        this.items = items;
    }

    @action removeById(id: T) {
        // TODO use metadata instead of hardcoded id
        this.items.splice(this.items.findIndex((item) => item.id === id), 1);
    }

    @action move(oldItemIndex: number, newItemIndex: number) {
        this.items = arrayMove(this.items, oldItemIndex, newItemIndex);
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    setRequestParameters(requestParameters: {[string]: string} ) {
        this.requestParameters = requestParameters;
    }

    loadItems(itemIds: ?Array<T>) {
        if (!itemIds || itemIds.length === 0) {
            this.set([]);
            return;
        }

        this.setLoading(true);
        return ResourceRequester.getList(this.resourceKey, {
            locale: this.locale ? this.locale.get() : undefined,
            [this.idFilterParameter]: itemIds.join(','),
            limit: undefined,
            page: 1,
            ...this.requestParameters,
        }).then(action((data) => {
            this.set(data._embedded[this.resourceKey]);
            this.setLoading(false);
        }));
    }
}
