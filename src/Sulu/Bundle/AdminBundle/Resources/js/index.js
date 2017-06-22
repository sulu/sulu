// @flow
import React from 'react';
import {render} from 'react-dom';
import createHistory from 'history/createHashHistory';
import Application from './containers/Application';
import {viewStore} from './containers/ViewRenderer';
import Router, {routeStore} from './services/Router';

viewStore.add('hello_world', () => (<h1>Hello World!</h1>));

routeStore.add({
    name: 'hello_world',
    view: 'hello_world',
    pattern: '/',
});

const router = new Router(createHistory());

render(
    <Application router={router} />,
    document.getElementById('react-root')
);
