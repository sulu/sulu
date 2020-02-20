// @flow
import type {Node} from 'react';
import type {ItemActionConfig} from '../../../containers/List/types';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import List from '../../../views/List/List';
import ListStore from '../../../containers/List/stores/ListStore';

export default class AbstractListItemAction {
    listStore: ListStore;
    list: List;
    router: Router;
    locales: ?Array<string>;
    resourceStore: ?ResourceStore;
    options: {[key: string]: mixed};

    constructor(
        listStore: ListStore,
        list: List,
        router: Router,
        locales?: Array<string>,
        resourceStore?: ResourceStore,
        options: {[key: string]: mixed}
    ) {
        this.listStore = listStore;
        this.list = list;
        this.router = router;
        this.locales = locales;
        this.resourceStore = resourceStore;
        this.options = options;
    }

    setLocales(locales: Array<string>) {
        this.locales = locales;
    }

    getNode(): Node {
        return null;
    }

    // eslint-disable-next-line no-unused-vars
    getItemActionConfig(item: ?Object): ItemActionConfig {
        throw new Error('The getItemActionConfig method must be implemented by the sub class!');
    }
}
