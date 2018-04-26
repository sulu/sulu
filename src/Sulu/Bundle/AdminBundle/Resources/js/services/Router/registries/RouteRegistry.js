// @flow
import {extendObservable} from 'mobx';
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

            const route = extendObservable({}, {
                ...routeConfig,
                children: [],
                parent: undefined,
            });
            this.routes[route.name] = route;
        });

        routeConfigs.forEach((routeConfig) => {
            const routeParent = routeConfig.parent;
            if (!routeParent) {
                return;
            }

            this.routes[routeConfig.name].parent = this.routes[routeParent];
            this.routes[routeParent].children.push(this.routes[routeConfig.name]);
        });
    }

    get(name: string): Route {
        if (!(name in this.routes)) {
            throw new Error('The route with the name "' + name + '" does not exist');
        }

        return this.routes[name];
    }

    getAll(): RouteMap {
        return this.routes;
    }
}

export default new RouteRegistry();
