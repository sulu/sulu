// @flow
import type {Route} from '../Route';
import type {RouteMap} from '../RouteMap';

class RouteStore {
    routes: RouteMap;

    constructor() {
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
