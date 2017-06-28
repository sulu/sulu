// @flow
import type {Route, RouteMap} from '../types';

class RouteStore {
    routes: RouteMap;

    constructor() {
        this.clear();
    }

    clear() {
        this.routes = {};
    }

    add(route: Route) {
        this.routes[route.name] = route;
    }

    get(name: string): Route {
        return this.routes[name];
    }

    getAll(): RouteMap {
        return this.routes;
    }
}

export default new RouteStore();
