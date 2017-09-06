// @flow
import {action, autorun, computed, observable} from 'mobx';
import pathToRegexp, {compile} from 'path-to-regexp';
import type {Route} from './types';
import routeStore from './stores/RouteStore';

export default class Router {
    history: Object;
    @observable currentRoute: Route;
    @observable currentParameters: Object;
    @observable currentSearchParameters: Object;

    constructor(history: Object) {
        this.history = history;

        this.history.listen((location) => {
            this.match(location.pathname, location.search);
        });

        autorun(() => {
            const {pathname, search} = this.history.location;
            const currentUrl = this.url;
            const historyUrl = pathname + search;
            if (currentUrl !== historyUrl) {
                this.history.push(currentUrl || historyUrl);
            }
        });
    }

    match(path: string, queryString: string) {
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

            const URLSearchParameters = new URLSearchParams(queryString);
            const searchParameters = {};
            for (const [key, value] of URLSearchParameters) {
                searchParameters[key] = value;
            }

            this.navigate(name, parameters, searchParameters);

            break;
        }
    }

    @action navigate(name: string, parameters: Object = {}, searchParameters: Object = {}) {
        const currentRoute = routeStore.get(name);
        const currentParameters = {...currentRoute.parameters, ...parameters};
        const currentSearchParameters = searchParameters;

        if (this.currentRoute
            && currentRoute
            && this.currentRoute.name === currentRoute.name
            && this.compareParameters(this.currentParameters, currentParameters)
            && this.compareParameters(this.currentSearchParameters, currentSearchParameters)
        ) {
            return;
        }

        this.currentRoute = currentRoute;
        this.currentParameters = currentParameters;
        this.currentSearchParameters = currentSearchParameters;
    }

    compareParameters(currentParameters: Object, parameters: Object) {
        for (const key in parameters) {
            if (currentParameters[key] !== parameters[key]) {
                return false;
            }
        }

        for (const key in currentParameters) {
            if (currentParameters[key] !== parameters[key]) {
                return false;
            }
        }

        return true;
    }

    @computed get url(): string {
        if (!this.currentRoute) {
            return '';
        }

        const url = compile(this.currentRoute.path)(this.currentParameters);
        const searchParameters = new URLSearchParams();
        Object.keys(this.currentSearchParameters).forEach((currentSearchParameterKey) => {
            searchParameters.set(currentSearchParameterKey, this.currentSearchParameters[currentSearchParameterKey]);
        });
        const queryString = searchParameters.toString();

        return url + (queryString ? '?' + queryString : '');
    }
}
