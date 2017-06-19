// @flow
import React from 'react';
import {render} from 'react-dom';
import Application from './containers/Application';
import {viewStore} from './containers/ViewRenderer';
import HelloWorld from './views/HelloWorld';

viewStore.add('hello_world', HelloWorld);

render(
    <Application />,
    document.getElementById('react-root')
);
