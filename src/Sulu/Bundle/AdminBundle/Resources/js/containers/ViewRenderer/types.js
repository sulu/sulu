// @flow
import type {ComponentType, Element} from 'react';
import Router from '../../services/Router';
import type {Route} from '../../services/Router';

export type ViewProps = {
    router: Router,
    route: Route,
    children: (?Object) => Element<View> | null,
};

export type View = ComponentType<ViewProps>;
