// @flow
import type {Route, RouteConfig, RouteMap} from '../types';

class RouteRegistry {
    routes: RouteMap;

    constructor() {
        this.clear();
    }

    clear() {
        this.routes = {};
    }

    addCollection(routeConfigs: Array<RouteConfig>) {
        routeConfigs.forEach((routeConfig) => {
            if (routeConfig.name in this.routes) {
                throw new Error('The name "' + routeConfig.name + '" has already been used for another route');
            }

            const route = {
                ...routeConfig,
                children: [],
                parent: undefined,
            };
            this.routes[route.name] = route;
        });

        routeConfigs.forEach((routeConfig) => {
            if (!routeConfig.parent) {
                return;
            }

            this.routes[routeConfig.name].parent = this.routes[routeConfig.parent];
            this.routes[routeConfig.parent].children.push(this.routes[routeConfig.name]);
        });
    }

    get(name: string): Route {
        return this.routes[name];
    }

    getAll(): RouteMap {
        return this.routes;
    }
}

export default new RouteRegistry();
