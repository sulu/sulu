// @flow
import type {Node} from 'react';
import type {ToolbarAction, ToolbarItemConfig} from '../../../containers/Toolbar/types';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import List from '../../../views/List/List';
import ListStore from '../../../containers/List/stores/ListStore';

export default class AbstractListToolbarAction implements ToolbarAction {
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

    getToolbarItemConfig(): ?ToolbarItemConfig {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }
}
