// @flow
import Router, {routeStore} from './services/Router';
import Application from './containers/Application';
import Form from './views/Form';
import List from './views/List';
import React from 'react';
import Requester from './services/Requester';
import createHistory from 'history/createHashHistory';
import log from 'loglevel';
import {render} from 'react-dom';
import {setTranslations} from './services/Translator';
import {useStrict} from 'mobx';
import {viewStore} from './containers/ViewRenderer';

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
