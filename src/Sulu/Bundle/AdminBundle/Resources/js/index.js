// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {useStrict} from 'mobx';
import Requester from './services/Requester';
import Router, {routeRegistry} from './services/Router';
import {setTranslations} from './services/Translator';
import Input from './components/Input';
import Application from './containers/Application';
import {fieldRegistry} from './containers/Form';
import {viewRegistry} from './containers/ViewRenderer';
import {datagridAdapterRegistry, TableAdapter, FolderAdapter, ColumnListAdapter} from './containers/Datagrid';
import Form from './views/Form';
import ResourceTabs from './views/ResourceTabs';
import List from './views/List';
import {bundlesReadyPromise, bundleReady} from './services/Bundles';

useStrict(true);

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

viewRegistry.add('sulu_admin.form', Form);
viewRegistry.add('sulu_admin.resource_tabs', ResourceTabs);
viewRegistry.add('sulu_admin.list', List);

datagridAdapterRegistry.add('table', TableAdapter);
datagridAdapterRegistry.add('folder', FolderAdapter);
datagridAdapterRegistry.add('column_list', ColumnListAdapter);

fieldRegistry.add('text_line', Input);

const translationPromise = Requester.get('/admin/v2/translations?locale=en')
    .then((response) => setTranslations(response));

const configPromise = Requester.get('/admin/v2/config')
    .then((response) => routeRegistry.addCollection(response.routes));

Promise.all([
    translationPromise,
    configPromise,
    bundlesReadyPromise,
]).then(() => {
    const router = new Router(createHistory());
    render(<Application router={router} />, document.getElementById('application'));
});

bundleReady();
