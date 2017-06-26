// @flow
export type Route = {
    name: string,
    view: string,
    path: string,
    parameters?: Object,
};

export type RouteMap = {[string]: Route};
