// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {ResourceRequester} from '../../services';

export default class SingleSelectionStore {
    @observable item: ?Object;
    @observable loading: boolean = false;
    resourceKey: string;
    locale: ?IObservableValue<string>;

    constructor(
        resourceKey: string,
        selectedItemId: ?string | number,
        locale: ?IObservableValue<string>
    ) {
        this.resourceKey = resourceKey;
        this.locale = locale;
        if (selectedItemId) {
            this.loadItem(selectedItemId);
        }
    }

    @action set(item: Object) {
        this.item = item;
    }

    @action clear() {
        this.item = undefined;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action loadItem = (itemId: ?string | number) => {
        if (!itemId) {
            this.item = undefined;
            return;
        }

        this.setLoading(true);
        return ResourceRequester.get(this.resourceKey, {
            id: itemId,
            locale: this.locale ? this.locale.get() : undefined,
        }).then(action((data) => {
            this.item = data;
            this.setLoading(false);
        }));
    };
}
