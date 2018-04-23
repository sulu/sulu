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
import {
    Assignment,
    DatePicker,
    fieldRegistry,
    Input,
    ResourceLocator,
    SingleSelect,
    TextArea,
    Time,
} from './containers/Form';
import FieldBlocks from './containers/FieldBlocks';
import {viewRegistry} from './containers/ViewRenderer';
import {navigationRegistry} from './containers/Navigation';
import resourceMetadataStore from './stores/ResourceMetadataStore';
import {
    ColumnListAdapter,
    datagridAdapterRegistry,
    datagridFieldTransformerRegistry,
    FolderAdapter,
    TableAdapter,
    BytesFieldTransformer,
    DateFieldTransformer,
    DateTimeFieldTransformer,
    StringFieldTransformer,
    ThumbnailFieldTransformer,
} from './containers/Datagrid';
import Form from './views/Form';
import ResourceTabs from './views/ResourceTabs';
import Datagrid from './views/Datagrid';
import {bundleReady, bundlesReadyPromise} from './services/Bundles';
import type {FieldTypeProps} from './types';

export type {FieldTypeProps};

// Bug in flow: https://github.com/facebook/flow/issues/6186
// $FlowFixMe:
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
    fieldRegistry.add('time', Time);

    const assignmentConfigs = fieldTypesConfig['assignment'];
    if (assignmentConfigs) {
        for (const assignmentKey in assignmentConfigs) {
            fieldRegistry.add(assignmentKey, Assignment, assignmentConfigs[assignmentKey]);
        }
    }
}

function registerDatagridFieldTypes() {
    datagridFieldTransformerRegistry.add('bytes', new BytesFieldTransformer());
    datagridFieldTransformerRegistry.add('date', new DateFieldTransformer());
    datagridFieldTransformerRegistry.add('datetime', new DateTimeFieldTransformer());
    datagridFieldTransformerRegistry.add('string', new StringFieldTransformer());
    datagridFieldTransformerRegistry.add('thumbnails', new ThumbnailFieldTransformer());

    // TODO: Remove this type when not needed anymore
    datagridFieldTransformerRegistry.add('title', new StringFieldTransformer());
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

const translationPromise = Requester.get('/admin/v2/translations?locale=en');

const configPromise = Requester.get('/admin/v2/config');

Promise.all([
    translationPromise,
    configPromise,
    bundlesReadyPromise,
]).then(([translationResponse, configResponse]) => {
    setTranslations(translationResponse);

    registerFieldTypes(configResponse['sulu_admin']['field_type_options']);
    routeRegistry.addCollection(configResponse['sulu_admin'].routes);
    navigationRegistry.set(configResponse['sulu_admin'].navigation);
    resourceMetadataStore.setEndpoints(configResponse['sulu_admin'].endpoints);

    registerDatagridFieldTypes();

    startApplication();
});

export {configPromise};

bundleReady();
