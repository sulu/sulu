// @flow
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {useStrict} from 'mobx';
import Requester from './services/Requester';
import Router, {routeStore} from './services/Router';
import {setTranslations} from './services/Translator';
import Application from './containers/Application';
import {viewStore} from './containers/ViewRenderer';
import Form from './views/Form';
import List from './views/List';

useStrict(true);

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.ERROR : log.levels.TRACE);

viewStore.add('sulu_admin.list', List);
viewStore.add('sulu_admin.form', Form);

function startApplication() {
    const router = new Router(createHistory());
    render(<Application router={router} />, document.getElementById('application'));
}

const translationPromise = Requester.get('/admin/v2/translations?locale=en')
    .then((response) => setTranslations(response));

const configPromise = Requester.get('/admin/v2/config')
    .then((response) => routeStore.addCollection(response.routes));

Promise.all([translationPromise, configPromise]).then(startApplication);
