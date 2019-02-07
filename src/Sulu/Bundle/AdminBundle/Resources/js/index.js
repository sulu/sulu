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
import metadataStore from './stores/MetadataStore';
import userStore, {logoutOnUnauthorizedResponse} from './stores/UserStore';
import {bundleReady, Config, resourceEndpointRegistry} from './services';
import initializer from './services/Initializer';
import ResourceTabs from './views/ResourceTabs';
import Datagrid from './views/Datagrid';
import CKEditor5 from './containers/TextEditor/adapters/CKEditor5';
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
import FieldBlocks, {
    blockPreviewTransformerRegistry,
    SelectBlockPreviewTransformer,
    SingleSelectBlockPreviewTransformer,
    SmartContentBlockPreviewTransformer,
    StringBlockPreviewTransformer,
    StripHtmlBlockPreviewTransformer,
} from './containers/FieldBlocks';
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

const FIELD_TYPE_COLOR = 'color';
const FIELD_TYPE_EMAIL = 'email';
const FIELD_TYPE_NUMBER = 'number';
const FIELD_TYPE_PHONE = 'phone';
const FIELD_TYPE_SELECT = 'select';
const FIELD_TYPE_SINGLE_SELECT = 'single_select';
const FIELD_TYPE_SMART_CONTENT = 'smart_content';
const FIELD_TYPE_TEXT_AREA = 'text_area';
const FIELD_TYPE_TEXT_EDITOR = 'text_editor';
const FIELD_TYPE_TEXT_LINE = 'text_line';
const FIELD_TYPE_URL = 'url';

initializer.addUpdateConfigHook('sulu_admin', (config: Object, initialized: boolean) => {
    if (!initialized) {
        registerBlockPreviewTransformers();
        registerDatagridAdapters();
        registerDatagridFieldTransformers();
        registerFieldTypes(config.fieldTypeOptions);
        registerTextEditors();
        registerToolbarActions();
        registerViews();
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
    fieldRegistry.add(FIELD_TYPE_COLOR, ColorPicker);
    fieldRegistry.add('date', DatePicker, {dateFormat: true, timeFormat: false});
    fieldRegistry.add('datetime', DatePicker, {dateFormat: true, timeFormat: true});
    fieldRegistry.add(FIELD_TYPE_EMAIL, Email);
    fieldRegistry.add(FIELD_TYPE_SELECT, Select);
    fieldRegistry.add(FIELD_TYPE_NUMBER, Number);
    fieldRegistry.add('password_confirmation', PasswordConfirmation);
    fieldRegistry.add(FIELD_TYPE_PHONE, Phone);
    fieldRegistry.add('resource_locator', ResourceLocator, {generationUrl: Config.endpoints.generateUrl});
    fieldRegistry.add(FIELD_TYPE_SMART_CONTENT, SmartContent);
    fieldRegistry.add(FIELD_TYPE_SINGLE_SELECT, SingleSelect);
    fieldRegistry.add(FIELD_TYPE_TEXT_AREA, TextArea);
    fieldRegistry.add(FIELD_TYPE_TEXT_EDITOR, TextEditor);
    fieldRegistry.add(FIELD_TYPE_TEXT_LINE, Input);
    fieldRegistry.add('time', DatePicker, {dateFormat: false, timeFormat: true});
    fieldRegistry.add(FIELD_TYPE_URL, Url);

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

function registerBlockPreviewTransformers() {
    blockPreviewTransformerRegistry.add(FIELD_TYPE_COLOR, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_EMAIL, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_NUMBER, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_PHONE, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_SELECT, new SelectBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_SINGLE_SELECT, new SingleSelectBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_SMART_CONTENT, new SmartContentBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TEXT_AREA, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TEXT_EDITOR, new StripHtmlBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TEXT_LINE, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_URL, new StringBlockPreviewTransformer());
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
    resourceEndpointRegistry.clear();

    routeRegistry.addCollection(config.routes);
    metadataStore.endpoint = config.endpoints.metadata;
    navigationRegistry.set(config.navigation);
    resourceEndpointRegistry.setEndpoints(config.resourceMetadataEndpoints);
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

    render(
        <Application appVersion={Config.appVersion} router={router} suluVersion={Config.suluVersion} />,
        applicationElement
    );
}

startApplication();

bundleReady();
