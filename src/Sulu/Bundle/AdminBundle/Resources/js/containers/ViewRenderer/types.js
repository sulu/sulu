// @flow
import type {Component, Element} from 'react';
import Router from '../../services/Router';
import type {Route} from '../../services/Router';

export type ViewProps = {
    router: Router,
    route: Route,
    children?: (?Object) => Element<*> | null,
};

export type View = Class<Component<ViewProps & *>>;
