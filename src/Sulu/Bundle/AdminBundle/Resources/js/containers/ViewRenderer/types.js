// @flow
import type {Component, Element} from 'react';
import Router from '../../services/Router';
import type {AttributeMap, Route} from '../../services/Router';

export type ViewProps = {
    router: Router,
    route: Route,
    children?: (?Object) => Element<*> | null,
};

interface GetDerivedRouteAttributesInterface {
    +getDerivedRouteAttributes?: (route: Route, attributes: AttributeMap) => Object,
}

export type View = Class<Component<ViewProps & *>> & GetDerivedRouteAttributesInterface;
