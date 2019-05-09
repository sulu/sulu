// @flow
import type {Component, Element} from 'react';
import Router from '../../services/Router';
import type {AttributeMap, Route} from '../../services/Router';

export type ViewProps = {
    children?: (?Object) => Element<*> | null,
    route: Route,
    router: Router,
};

interface GetDerivedRouteAttributesInterface {
    +getDerivedRouteAttributes?: (route: Route, attributes: AttributeMap) => Object,
}

export type View = Class<Component<ViewProps & *>> & GetDerivedRouteAttributesInterface;
