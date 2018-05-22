// @flow
import type {ComponentType, Element} from 'react';
import Router from '../../services/Router';
import type {Route} from '../../services/Router';

export type ViewProps = {
    children: (?Object) => Element<View> | null,
    route: Route,
    router: Router,
};

export type View = ComponentType<ViewProps>;
