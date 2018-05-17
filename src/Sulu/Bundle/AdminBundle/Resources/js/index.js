// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import Requester from './services/Requester';
import Router from './services/Router';
import Application from './containers/Application';
import {updateRouterAttributesFromView} from './containers/ViewRenderer';
import {bundleReady} from './services/Bundles';
import initializer from './services/Initializer';
import type {FieldTypeProps} from './types';
import userStore from './stores/UserStore';

export type {FieldTypeProps};

configure({enforceActions: true});

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

Requester.handleResponseHooks.push((response: Object) => {
    if (response.status === 401) {
        userStore.setLoggedIn(false);
    }
});

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

startApplication();

initializer.initialize();

bundleReady();
