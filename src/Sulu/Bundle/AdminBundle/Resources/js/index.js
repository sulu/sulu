// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import Requester from './services/Requester';
import Router, {routeRegistry} from './services/Router';
import {setTranslations, translate} from './utils/Translator';
import Application from './containers/Application';
import {Assignment, DatePicker, fieldRegistry, Input, ResourceLocator, SingleSelect, TextArea} from './containers/Form';
import FieldBlocks from './containers/FieldBlocks';
import {viewRegistry} from './containers/ViewRenderer';
import {navigationRegistry} from './containers/Navigation';
import {ColumnListAdapter, datagridAdapterRegistry, FolderAdapter, TableAdapter} from './containers/Datagrid';
import resourceMetadataStore from './stores/ResourceMetadataStore';
import Form from './views/Form';
import ResourceTabs from './views/ResourceTabs';
import Datagrid from './views/Datagrid';
import {bundleReady, bundlesReadyPromise} from './services/Bundles';
import type {FieldTypeProps} from './types';

export type {FieldTypeProps};

configure({enforceActions: true});

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

viewRegistry.add('sulu_admin.form', Form);
viewRegistry.add('sulu_admin.resource_tabs', ResourceTabs);
viewRegistry.add('sulu_admin.datagrid', Datagrid);

datagridAdapterRegistry.add('column_list', ColumnListAdapter);
datagridAdapterRegistry.add('folder', FolderAdapter);
datagridAdapterRegistry.add('table', TableAdapter);

function registerFieldTypes() {
    fieldRegistry.add('block', FieldBlocks);
    fieldRegistry.add('date', DatePicker);
    fieldRegistry.add('resource_locator', ResourceLocator);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);

    // TODO move to correct bundle or even allow to register somehow via the config request
    fieldRegistry.add('snippet', Assignment, {
        adapter: 'table',
        displayProperties: [
            'title',
        ],
        icon: 'su-snippet',
        label: translate('sulu_snippet.assignment_label'),
        resourceKey: 'snippets',
        overlayTitle: translate('sulu_snippet.assignment_overlay_title'),
    });

    fieldRegistry.add('internal_links', Assignment, {
        adapter: 'column_list',
        displayProperties: [
            'title',
        ],
        icon: 'su-document',
        label: translate('sulu_content.assignment_label'),
        resourceKey: 'pages',
        overlayTitle: translate('sulu_content.assignment_overlay_title'),
    });
}

function startApplication() {
    const router = new Router(createHistory());
    const id = 'application';
    const applicationElement = document.getElementById(id);

    if (!applicationElement) {
        throw new Error('DOM element with ID "id" was not found!');
    }

    render(<Application router={router} />, applicationElement);
}

const translationPromise = Requester.get('/admin/v2/translations?locale=en')
    .then((response) => setTranslations(response));

const configPromise = Requester.get('/admin/v2/config').then((response) => {
    routeRegistry.addCollection(response['sulu_admin'].routes);
    navigationRegistry.set(response['sulu_admin'].navigation);
    resourceMetadataStore.setEndpoints(response['sulu_admin'].endpoints);

    return response;
});

Promise.all([
    translationPromise,
    configPromise,
    bundlesReadyPromise,
]).then(() => {
    registerFieldTypes();
    startApplication();
});

export {configPromise};

bundleReady();
