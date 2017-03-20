// @flow
import Route from './Route';
import pathToRegexp, {compile} from 'path-to-regexp';
import {action, autorun, computed, observable} from 'mobx';

type RouteMap = {[key: string]: Route};

export default class Router {
    history;
    routes: RouteMap;
    @observable currentRoute: Route;
    @observable currentParameters: Object;

    constructor(history, routes: RouteMap = {}) {
        this.history = history;
        this.routes = routes;

        this.history.listen((location) => {
            this.match(location.pathname);
        });

        autorun(() => {
            const path = this.url;
            if (path !== this.history.location.pathname) {
                this.history.push(path);
            }
        });
    }

    add(route: Route) {
        this.routes[route.name] = route;
    }

    match(path: string) {
        for (const key in this.routes) {
            const route = this.routes[key];
            const keys = [];
            const match = pathToRegexp(route.pattern, keys).exec(path);

            if (!match) {
                continue;
            }

            const parameters = {};
            for (let i = 1; i < match.length; i++) {
                parameters[keys[i - 1].name] = match[i];
            }

            this.navigate(key, parameters);

            break;
        }
    }

    @action navigate(name: string, parameters: Object) {
        const currentRoute = this.routes[name];
        const currentParameters = {...currentRoute.parameters, ...parameters};

        if (this.currentRoute
            && currentRoute
            && this.currentRoute.name === currentRoute.name
            && this.currentParameters
            && currentParameters
            && this.currentParameters.length === currentParameters.length
        ) {
            let match = true;

            for (let key in currentParameters) {
                if (this.currentParameters[key] !== currentParameters[key]) {
                    match = false;
                    break;
                }
            }

            if (match) {
                return;
            }
        }

        this.currentRoute = currentRoute;
        this.currentParameters = currentParameters;
    }

    @computed get url(): string {
        if (!this.currentRoute) {
            return '';
        }

        return compile(this.currentRoute.pattern)(this.currentParameters);
    }
}
