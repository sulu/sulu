// @flow
import {action, autorun, computed, observable} from 'mobx';
import equal from 'fast-deep-equal';
import pathToRegexp, {compile} from 'path-to-regexp';
import type {Route} from './types';
import routeStore from './stores/RouteStore';

export default class Router {
    history: Object;
    @observable route: Route;
    @observable attributes: Object;
    @observable query: Object;

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

            const attributes = {};
            for (let i= 1; i < match.length; i++) {
                attributes[names[i - 1].name] = match[i];
            }

            const search = new URLSearchParams(queryString);
            const query = {};
            search.forEach((value, key) => {
                query[key] = value;
            });

            this.navigate(name, attributes, query);

            break;
        }
    }

    @action navigate(name: string, attributes: Object = {}, query: Object = {}) {
        const route = routeStore.get(name);

        if (equal(this.route, route)
            && equal(this.attributes, attributes)
            && equal(this.query, query)
        ) {
            return;
        }

        this.route = route;
        this.attributes = attributes;
        this.query = query;
    }

    @computed get server(): Object {
        if (!this.route || !this.route.options) {
            return {};
        }

        return this.route.options;
    }

    @computed get url(): string {
        if (!this.route) {
            return '';
        }

        const url = compile(this.route.path)(this.attributes);
        const searchParameters = new URLSearchParams();
        Object.keys(this.query).forEach((currentSearchParameterKey) => {
            searchParameters.set(currentSearchParameterKey, this.query[currentSearchParameterKey]);
        });
        const queryString = searchParameters.toString();

        return url + (queryString ? '?' + queryString : '');
    }
}
