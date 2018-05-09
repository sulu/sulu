// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import Requester from './services/Requester';
import Router from './services/Router';
import Application from './containers/Application';
import {updateRouterAttributesFromView, viewRegistry} from './containers/ViewRenderer';
import Form from './views/Form';
import ResourceTabs from './views/ResourceTabs';
import Datagrid from './views/Datagrid';
import {bundleReady, bundlesReadyPromise} from './services/Bundles';
import initializer from './services/Initializer';
import type {FieldTypeProps} from './types';
import userStore from './stores/UserStore';

export type {FieldTypeProps};

configure({enforceActions: true});

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

Requester.handleResponseHooks.push((response: Object) => {
    if (response.status === 401) {
        userStore.clearUser();
    }
});

viewRegistry.add('sulu_admin.form', Form);
viewRegistry.add('sulu_admin.resource_tabs', ResourceTabs);
viewRegistry.add('sulu_admin.datagrid', Datagrid);

function startApplication() {
    const router = new Router(createHistory());
    router.addUpdateAttributesHook(updateRouterAttributesFromView);
    const id = 'application';
    const applicationElement = document.getElementById(id);

    if (!applicationElement) {
        throw new Error('DOM element with ID "id" was not found!');
    }

    render(<Application router={router} />, applicationElement);
}

initializer.registerDatagrid();

Promise.all([bundlesReadyPromise, initializer.initialize()]).then(() => {
    startApplication();
});

bundleReady();
