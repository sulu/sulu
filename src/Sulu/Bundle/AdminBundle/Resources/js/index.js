// @flow
import React from 'react';
import {render} from 'react-dom';
import {useStrict} from 'mobx';
import createHistory from 'history/createHashHistory';
import Application from './containers/Application';
import {viewStore} from './containers/ViewRenderer';
import Requester from './services/Requester';
import Router, {routeStore} from './services/Router';
import {setTranslations} from './services/Translator';
import Form from './views/Form';
import List from './views/List';

useStrict(true);

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
