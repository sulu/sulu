// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import Requester from './services/Requester';
import Router, {routeRegistry} from './services/Router';
import {setTranslations, translate} from './utils/Translator';
import TextArea from './components/TextArea';
import Application from './containers/Application';
import {fieldRegistry, Assignment, Input, ResourceLocator, SingleSelect} from './containers/Form';
import FieldBlocks from './containers/FieldBlocks';
import {viewRegistry} from './containers/ViewRenderer';
import {ColumnListAdapter, datagridAdapterRegistry, FolderAdapter, TableAdapter} from './containers/Datagrid';
import Form from './views/Form';
import ResourceTabs from './views/ResourceTabs';
import List from './views/List';
import {bundleReady, bundlesReadyPromise} from './services/Bundles';
import type {FieldTypeProps} from './types';

export type {FieldTypeProps};

configure({enforceActions: true});

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

viewRegistry.add('sulu_admin.form', Form);
viewRegistry.add('sulu_admin.resource_tabs', ResourceTabs);
viewRegistry.add('sulu_admin.list', List);

datagridAdapterRegistry.add('column_list', ColumnListAdapter);
datagridAdapterRegistry.add('folder', FolderAdapter);
datagridAdapterRegistry.add('table', TableAdapter);

function registerFieldTypes() {
    fieldRegistry.add('block', FieldBlocks);
    fieldRegistry.add('resource_locator', ResourceLocator);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);

    // TODO move to correct bundle or even allow to register somehow via the config request
    fieldRegistry.add('snippet', Assignment, {
        displayProperties: [
            'title',
        ],
        icon: 'su-document',
        label: translate('sulu_snippet.assignment_label'),
        resourceKey: 'snippets',
        title: translate('sulu_snippet.assignment_overlay_title'),
    });
    fieldRegistry.add('contact', Assignment, {
        displayProperties: [
            'firstName',
            'lastName',
        ],
        icon: 'su-user-1',
        label: translate('sulu_contact.contact_assignment_label'),
        resourceKey: 'contacts',
        title: translate('sulu_contact.contact_assignment_overlay_title'),
    });
    fieldRegistry.add('account', Assignment, {
        displayProperties: [
            'id',
            'name',
        ],
        icon: 'su-user-1',
        label: translate('sulu_contact.account_assignment_label'),
        resourceKey: 'accounts',
        title: translate('sulu_contact.account_assignment_overlay_title'),
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
