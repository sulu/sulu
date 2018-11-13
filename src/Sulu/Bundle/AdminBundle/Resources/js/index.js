// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import jexl from 'jexl';
import ResizeObserver from 'resize-observer-polyfill';
import Requester from './services/Requester';
import Router, {routeRegistry} from './services/Router';
import Application from './containers/Application';
import {updateRouterAttributesFromView, viewRegistry} from './containers/ViewRenderer';
import userStore, {logoutOnUnauthorizedResponse} from './stores/UserStore';
import {bundleReady, Config} from './services';
import initializer from './services/Initializer';
import ResourceTabs from './views/ResourceTabs';
import Datagrid from './views/Datagrid';
import CKEditor5 from './components/CKEditor5';
import {
    BoolFieldTransformer,
    BytesFieldTransformer,
    ColumnListAdapter,
    datagridAdapterRegistry,
    datagridFieldTransformerRegistry,
    DateFieldTransformer,
    DateTimeFieldTransformer,
    FolderAdapter,
    NumberFieldTransformer,
    StringFieldTransformer,
    TableAdapter,
    ThumbnailFieldTransformer,
    TimeFieldTransformer,
    TreeTableAdapter,
} from './containers/Datagrid';
import FieldBlocks from './containers/FieldBlocks';
import {
    Checkbox,
    ColorPicker,
    ChangelogLine,
    DatePicker,
    Email,
    fieldRegistry,
    Input,
    Select,
    Number,
    PasswordConfirmation,
    Phone,
    ResourceLocator,
    Selection,
    SingleSelect,
    SingleSelection,
    SmartContent,
    TextArea,
    TextEditor,
    Url,
} from './containers/Form';
import {textEditorRegistry} from './containers/TextEditor';
import Form, {
    DeleteToolbarAction,
    SaveToolbarAction,
    SaveWithPublishingToolbarAction,
    toolbarActionRegistry,
    TypeToolbarAction,
} from './views/Form';
import {navigationRegistry} from './containers/Navigation';
import resourceMetadataStore from './stores/ResourceMetadataStore';
import {smartContentConfigStore} from './containers/SmartContent';

// $FlowFixMe
configure({enforceActions: 'observed'});

if (!window.ResizeObserver) {
    window.ResizeObserver = ResizeObserver;
}

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

Requester.handleResponseHooks.push(logoutOnUnauthorizedResponse);

jexl.addTransform('values', (value: Array<*>) => Object.values(value));

initializer.addUpdateConfigHook('sulu_admin', (config: Object, initialized: boolean) => {
    if (!initialized) {
        registerViews();
        registerDatagridAdapters();
        registerDatagridFieldTransformers();
        registerFieldTypes(config.fieldTypeOptions);
        registerTextEditors();
        registerToolbarActions();
    }

    processConfig(config);

    userStore.setUser(config.user);
    userStore.setContact(config.contact);
    userStore.setLoggedIn(true);
});

function registerViews() {
    viewRegistry.add('sulu_admin.form', Form);
    viewRegistry.add('sulu_admin.resource_tabs', (ResourceTabs: any));
    viewRegistry.add('sulu_admin.datagrid', Datagrid);
}

function registerDatagridAdapters() {
    datagridAdapterRegistry.add('column_list', ColumnListAdapter);
    datagridAdapterRegistry.add('folder', FolderAdapter);
    datagridAdapterRegistry.add('table', TableAdapter);
    datagridAdapterRegistry.add('tree_table', TreeTableAdapter);
    datagridAdapterRegistry.add('tree_table_slim', TreeTableAdapter, {showHeader: false});
}

function registerDatagridFieldTransformers() {
    datagridFieldTransformerRegistry.add('bytes', new BytesFieldTransformer());
    datagridFieldTransformerRegistry.add('date', new DateFieldTransformer());
    datagridFieldTransformerRegistry.add('time', new TimeFieldTransformer());
    datagridFieldTransformerRegistry.add('datetime', new DateTimeFieldTransformer());
    datagridFieldTransformerRegistry.add('number', new NumberFieldTransformer());
    datagridFieldTransformerRegistry.add('string', new StringFieldTransformer());
    datagridFieldTransformerRegistry.add('thumbnails', new ThumbnailFieldTransformer());
    datagridFieldTransformerRegistry.add('bool', new BoolFieldTransformer());

    // TODO: Remove this type when not needed anymore
    datagridFieldTransformerRegistry.add('title', new StringFieldTransformer());
}

function registerFieldTypes(fieldTypeOptions) {
    fieldRegistry.add('block', FieldBlocks);
    fieldRegistry.add('changelog_line', ChangelogLine);
    fieldRegistry.add('checkbox', Checkbox);
    fieldRegistry.add('color', ColorPicker);
    fieldRegistry.add('date', DatePicker, {dateFormat: true, timeFormat: false});
    fieldRegistry.add('datetime', DatePicker, {dateFormat: true, timeFormat: true});
    fieldRegistry.add('email', Email);
    fieldRegistry.add('select', Select);
    fieldRegistry.add('number', Number);
    fieldRegistry.add('password_confirmation', PasswordConfirmation);
    fieldRegistry.add('phone', Phone);
    fieldRegistry.add('resource_locator', ResourceLocator, {generationUrl: Config.endpoints.generateUrl});
    fieldRegistry.add('smart_content', SmartContent);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);
    fieldRegistry.add('text_editor', TextEditor);
    fieldRegistry.add('time', DatePicker, {dateFormat: false, timeFormat: true});
    fieldRegistry.add('url', Url);

    registerFieldTypesWithOptions(fieldTypeOptions['selection'], Selection);
    registerFieldTypesWithOptions(fieldTypeOptions['single_selection'], SingleSelection);
}

function registerFieldTypesWithOptions(fieldTypeOptions, Component) {
    if (fieldTypeOptions) {
        for (const fieldTypeKey in fieldTypeOptions) {
            fieldRegistry.add(fieldTypeKey, Component, fieldTypeOptions[fieldTypeKey]);
        }
    }
}

function registerTextEditors() {
    textEditorRegistry.add('ckeditor5', CKEditor5);
}

function registerToolbarActions() {
    toolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    toolbarActionRegistry.add('sulu_admin.save_with_publishing', SaveWithPublishingToolbarAction);
    toolbarActionRegistry.add('sulu_admin.save', SaveToolbarAction);
    toolbarActionRegistry.add('sulu_admin.type', TypeToolbarAction);
}

function processConfig(config: Object) {
    routeRegistry.clear();
    navigationRegistry.clear();
    resourceMetadataStore.clear();

    routeRegistry.addCollection(config.routes);
    navigationRegistry.set(config.navigation);
    resourceMetadataStore.setEndpoints(config.resourceMetadataEndpoints);
    smartContentConfigStore.setConfig(config.smartContent);
}

function startApplication() {
    const router = new Router(createHistory());
    router.addUpdateAttributesHook(updateRouterAttributesFromView);

    initializer.initialize().then(() => {
        router.reload();
    });

    const id = 'application';
    const applicationElement = document.getElementById(id);

    if (!applicationElement) {
        throw new Error('DOM element with ID "id" was not found!');
    }

    render(<Application router={router} suluVersion={Config.suluVersion} />, applicationElement);
}

startApplication();

bundleReady();
