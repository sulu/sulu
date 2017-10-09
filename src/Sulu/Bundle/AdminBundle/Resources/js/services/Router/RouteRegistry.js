// @flow
import type {Route, RouteMap} from './types';

class RouteRegistry {
    routes: RouteMap;

    constructor() {
        this.clear();
    }

    clear() {
        this.routes = {};
    }

    add(route: Route) {
        if (route.name in this.routes) {
            throw new Error('The name "' + route.name + '" has already been used for another route');
        }
        this.routes[route.name] = route;
    }

    addCollection(routes: Array<Route>) {
        routes.forEach((route) => this.add(route));
    }

    get(name: string): Route {
        return this.routes[name];
    }

    getAll(): RouteMap {
        return this.routes;
    }
}

export default new RouteRegistry();
