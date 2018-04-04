// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import equal from 'fast-deep-equal';
import log from 'loglevel';
import pathToRegexp, {compile} from 'path-to-regexp';
import type {Route, AttributeMap} from './types';
import routeRegistry from './registries/RouteRegistry';

export default class Router {
    history: Object;
    @observable route: Route;
    @observable attributes: Object = {};
    @observable bindings: Map<string, IObservableValue<*>> = new Map();
    bindingDefaults: Map<string, ?string | number> = new Map();
    attributesHistory: {[string]: Array<AttributeMap>} = {};

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

    @action bind(key: string, value: IObservableValue<*>, defaultValue: ?string | number = undefined) {
        if (key in this.attributes) {
            // when the bound parameter is bound set the state of the passed observable to the current value once
            // required because otherwise the parameter will be overridden on the initial start of the application
            value.set(this.attributes[key]);
        }

        if (value.get() === undefined) {
            // when the observable value is not set we want it to be the default value
            value.set(defaultValue);
        }

        this.bindings.set(key, value);
        this.bindingDefaults.set(key, defaultValue);
    }

    @action clearBindings() {
        this.bindings.clear();
        this.bindingDefaults.clear();
    }

    match(path: string, queryString: string) {
        for (const name in routeRegistry.getAll()) {
            const route = routeRegistry.get(name);
            const names = [];
            const match = pathToRegexp(route.path, names).exec(path);

            if (!match) {
                continue;
            }

            const attributes = {};
            for (let i = 1; i < match.length; i++) {
                attributes[names[i - 1].name] = Router.tryParseNumber(match[i]);
            }

            const search = new URLSearchParams(queryString);
            search.forEach((value, key) => {
                attributes[key] = Router.tryParseNumber(value);
            });

            this.handleNavigation(name, attributes);

            break;
        }
    }

    handleNavigation(name: string, attributes: Object) {
        if (!this.isRouteChanging(name, attributes)) {
            return;
        }

        this.createAttributesHistory();
        this.update(name, attributes);
    }

    navigate(name: string, attributes: Object = {}) {
        this.clearBindings();
        this.handleNavigation(name, attributes);
    }

    restore(name: string, attributes: Object = {}) {
        if (!this.attributesHistory[name] || this.attributesHistory[name].length === 0) {
            this.update(name, attributes);
            return;
        }

        if (!this.isRouteChanging(name, attributes)) {
            return;
        }

        const attributesHistory = this.attributesHistory[name].pop();

        this.update(name, {...attributesHistory, ...attributes});
    }

    @action update(name: string, attributes: Object) {
        this.route = routeRegistry.get(name);

        const attributeDefaults = this.route.attributeDefaults;
        Object.keys(attributeDefaults).forEach((key) => {
            // set default attributes if not passed, to automatically set important omitted attributes everywhere
            // e.g. allows to always pass the default locale if nothing is passed
            if (attributes[key] !== undefined) {
                return;
            }
            attributes[key] = attributeDefaults[key];
        });

        this.attributes = attributes;

        for (const [key, observableValue] of this.bindings.entries()) {
            const value: any = this.attributes[key] || this.bindingDefaults.get(key);

            // Type unsafe comparison to not trigger a new navigation when only data type changes
            if (value != observableValue.get()) {
                observableValue.set(value);
            }
        }
    }

    @computed get url(): string {
        if (!this.route) {
            return '';
        }

        const keys = [];
        pathToRegexp(this.route.path, keys);
        const keyNames = keys.map((key) => key.name);

        const attributes = toJS(this.attributes);
        for (const [key, observableValue] of this.bindings.entries()) {
            const value = observableValue.get();
            attributes[key] = value;
        }

        const url = compile(this.route.path)(attributes);
        const searchParameters = new URLSearchParams();
        Object.keys(attributes).forEach((key) => {
            const value = attributes[key];
            if (keyNames.includes(key) || value == this.bindingDefaults.get(key)) {
                return;
            }
            searchParameters.set(key, value);
        });

        const queryString = searchParameters.toString();

        return url + (queryString ? '?' + queryString : '');
    }

    createAttributesHistory() {
        if (!this.route) {
            return;
        }

        if (!(this.route.name in this.attributesHistory)) {
            this.attributesHistory[this.route.name] = [];
        }

        this.attributesHistory[this.route.name].push(toJS(this.attributes));
    }

    isRouteChanging(name: string, attributes: Object) {
        const route = routeRegistry.get(name);

        return !(
            this.route
            && this.route.name === route.name
            && equal(this.attributes, attributes)
        );
    }

    static tryParseNumber(value: string) {
        if (isNaN(value)) {
            return value;
        }

        return parseFloat(value);
    }
}
