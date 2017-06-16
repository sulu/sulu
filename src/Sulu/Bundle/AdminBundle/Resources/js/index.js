// @flow
import React from 'react';
import {render} from 'react-dom';
import Application from './containers/Application';
import {addView} from './services/ViewRegistry';
import HelloWorld from './views/HelloWorld';

addView('hello_world', HelloWorld);

render(<Application />, document.getElementById('react-root'));
