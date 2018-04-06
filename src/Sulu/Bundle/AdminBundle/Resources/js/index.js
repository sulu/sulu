// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import Requester from './services/Requester';
import Router, {routeRegistry} from './services/Router';
import {setTranslations} from './utils/Translator';
import Application from './containers/Application';
import {fieldRegistry, Assignment, DatePicker, Input, ResourceLocator, SingleSelect, TextArea} from './containers/Form';
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

function registerFieldTypes(fieldTypesConfig) {
    fieldRegistry.add('block', FieldBlocks);
    fieldRegistry.add('date', DatePicker);
    fieldRegistry.add('resource_locator', ResourceLocator);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);

    const assignmentConfigs = fieldTypesConfig['assignment'];
    for (const assignmentKey in assignmentConfigs) {
        fieldRegistry.add(assignmentKey, Assignment, assignmentConfigs[assignmentKey]);
    }
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
]).then((values) => {
    registerFieldTypes(values[1]['sulu_admin']['field_types']);
    startApplication();
});

export {configPromise};

bundleReady();
