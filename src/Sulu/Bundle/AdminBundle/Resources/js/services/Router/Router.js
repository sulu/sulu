// @flow
import {action, autorun, computed, observable} from 'mobx';
import equal from 'fast-deep-equal';
import log from 'loglevel';
import pathToRegexp, {compile} from 'path-to-regexp';
import type {Route} from './types';
import routeStore from './stores/RouteStore';

export default class Router {
    history: Object;
    @observable route: Route;
    @observable attributes: Object = {};
    @observable query: Object = {};
    @observable queryBinds: Map<string, observable> = new Map();
    queryBindDefaults: Map<string, ?string> = new Map();

    constructor(history: Object) {
        this.history = history;

        this.history.listen((location) => {
            log.info('URL was changed to ' + location.pathname + location.search);
            this.match(location.pathname, location.search);
        });

        autorun(() => {
            const {pathname, search} = this.history.location;
            const currentUrl = this.url;
            const historyUrl = pathname + search;
            if (currentUrl !== historyUrl) {
                // have to use the historyUrl as a fallback, because currentUrl could be undefined and break the routing
                const url = currentUrl || historyUrl;
                log.info('Router changes URL to ' + url);
                this.history.push(url);
            }
        });
    }

    @action bindQuery(key: string, value: observable, defaultValue: ?string = undefined) {
        if (key in this.query) {
            // when the query parameter is bound set the state of the passed observable to the current value once
            // required because otherwise the parameter will be overridden on the initial start of the application
            value.set(this.query[key]);
        }

        if (typeof(value.get()) === 'undefined') {
            // when the observable value is not set we want it to be the default value
            value.set(defaultValue);
        }

        this.queryBinds.set(key, value);
        this.queryBindDefaults.set(key, defaultValue);
    }

    @action unbindQuery(key: string) {
        this.queryBinds.delete(key);
        this.queryBindDefaults.delete(key);
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

        for (const [key, observableValue] of this.queryBinds.entries()) {
            observableValue.set(this.query[key] || this.queryBindDefaults.get(key));
        }
    }

    @computed get url(): string {
        if (!this.route) {
            return '';
        }

        const url = compile(this.route.path)(this.attributes);
        const searchParameters = new URLSearchParams();
        Object.keys(this.query).forEach((key) => {
            searchParameters.set(key, this.query[key]);
        });

        for (const [key, observableValue] of this.queryBinds.entries()) {
            const value = observableValue.get();
            if (value == this.queryBindDefaults.get(key)) {
                searchParameters.delete(key);
                break;
            }

            searchParameters.set(key, value);
        }

        const queryString = searchParameters.toString();

        return url + (queryString ? '?' + queryString : '');
    }
}
