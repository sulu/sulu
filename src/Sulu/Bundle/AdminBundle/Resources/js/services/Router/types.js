// @flow
export type RouteConfig = {|
    attributeDefaults: AttributeMap,
    name: string,
    options: Object,
    parent?: string,
    path: string,
    rerenderAttributes: Array<string>,
    view: string,
|};

export type Route = {|
    attributeDefaults: AttributeMap,
    children: Array<Route>,
    name: string,
    options: Object,
    parent: ?Route,
    path: string,
    rerenderAttributes: Array<string>,
    view: string,
|};

export type AttributeMap = {[string]: string};

export type RouteMap = {[string]: Route};

export type UpdateAttributesHook = (route: Route, attributes: AttributeMap) => AttributeMap;

export type UpdateRouteHook = (
    route: ?Route,
    attributes: ?AttributeMap,
    updateRouteMethod: ?UpdateRouteMethod
) => boolean;

export type UpdateRouteMethod = (route: string, attributes: AttributeMap) => void;
