// @flow
import React from 'react';
import {render} from 'react-dom';
import createHistory from 'history/createHashHistory';
import Application from './Application';
import Router from './Routing/Router';
import Route from './Routing/Route';
import {addView} from './ViewRegistry';

const appContainer = document.createElement('div');
document.getElementsByTagName('body')[0].append(appContainer);

const router = new Router(createHistory());
router.add(new Route('security_login', 'security_login', ''));
router.navigate('security_login');

setTimeout(() => render(<Application router={router} />, appContainer), 0);

export {addView};
