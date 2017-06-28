// @flow
import {action, autorun, computed, observable} from 'mobx';
import pathToRegexp, {compile} from 'path-to-regexp';
import type {Route} from './types';
import routeStore from './stores/RouteStore';

export default class Router {
    history: Object;
    @observable currentRoute: Route;
    @observable currentParameters: Object;

    constructor(history: Object) {
        this.history = history;

        this.history.listen((location) => {
            this.match(location.pathname);
        });

        autorun(() => {
            const path = this.url;
            if (path !== this.history.location.pathname) {
                this.history.push(path || this.history.location.pathname);
            }
        });
    }

    match(path: string) {
        for (const name in routeStore.getAll()) {
            const route = routeStore.get(name);
            const names = [];
            const match = pathToRegexp(route.path, names).exec(path);

            if (!match) {
                continue;
            }

            const parameters = {};
            for (let i= 1; i < match.length; i++) {
                parameters[names[i - 1].name] = match[i];
            }

            this.navigate(name, parameters);

            break;
        }
    }

    @action navigate(name: string, parameters: Object = {}) {
        const currentRoute = routeStore.get(name);
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

        return compile(this.currentRoute.path)(this.currentParameters);
    }
}
