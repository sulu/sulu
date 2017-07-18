// @flow
export type Route = {
    name: string,
    view: string,
    path: string,
    options: Object,
};

export type RouteMap = {[string]: Route};
