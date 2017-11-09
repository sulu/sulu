// @flow
export type RouteConfig = {
    name: string,
    parent?: string,
    view: string,
    path: string,
    options: Object,
    attributeDefaults: Object,
};

export type Route = {
    name: string,
    parent: ?Route,
    children: Array<Route>,
    view: string,
    path: string,
    options: Object,
    attributeDefaults: Object,
};

export type RouteMap = {[string]: Route};
