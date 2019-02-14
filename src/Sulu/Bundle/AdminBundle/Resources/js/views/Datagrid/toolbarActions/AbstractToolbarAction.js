// @flow
import type {Node} from 'react';
import type {ToolbarAction, ToolbarItemConfig} from '../../../containers/Toolbar/types';
import Router from '../../../services/Router';
import Datagrid from '../../../views/Datagrid/Datagrid';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';

export default class AbstractToolbarAction implements ToolbarAction {
    datagridStore: DatagridStore;
    datagrid: Datagrid;
    router: Router;
    locales: ?Array<string>;

    constructor(datagridStore: DatagridStore, datagrid: Datagrid, router: Router, locales?: Array<string>) {
        this.datagridStore = datagridStore;
        this.datagrid = datagrid;
        this.router = router;
        this.locales = locales;
    }

    setLocales(locales: Array<string>) {
        this.locales = locales;
    }

    getNode(): Node {
        return null;
    }

    getToolbarItemConfig(): ToolbarItemConfig {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }
}
