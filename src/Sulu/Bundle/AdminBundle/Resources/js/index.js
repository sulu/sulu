// @flow
import React from 'react';
import {render} from 'react-dom';
import {useStrict} from 'mobx';
import createHistory from 'history/createHashHistory';
import Application from './containers/Application';
import {viewStore} from './containers/ViewRenderer';
import Router, {routeStore} from './services/Router';

useStrict(true);

viewStore.add('hello_world', () => (<h1>Hello World!</h1>));

routeStore.add({
    name: 'hello_world',
    view: 'hello_world',
    path: '/',
});

const router = new Router(createHistory());
router.navigate('hello_world');

render(
    <Application router={router} />,
    document.getElementById('application')
);
