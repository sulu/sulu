// @flow
import {action, autorun, computed, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import equal from 'fast-deep-equal';
import log from 'loglevel';
import pathToRegexp, {compile} from 'path-to-regexp';
import type {AttributeMap, Route, UpdateAttributesHook, UpdateRouteHook, UpdateRouteMethod} from './types';
import routeRegistry from './registries/RouteRegistry';

export default class Router {
    history: Object;
    @observable route: Route;
    @observable attributes: Object = {};
    @observable bindings: Map<string, IObservableValue<*>> = new Map();
    bindingDefaults: Map<string, ?string | number | boolean> = new Map();
    attributesHistory: {[string]: Array<AttributeMap>} = {};
    updateRouteHooks: Array<UpdateRouteHook> = [];
    updateAttributesHooks: Array<UpdateAttributesHook> = [];
    redirectFlag: boolean = false;

    constructor(history: Object) {
        this.history = history;

        this.history.listen((location) => {
            log.info('URL was changed to "' + location.pathname + location.search + '"');
            this.match(location.pathname, location.search);
        });

        autorun(() => {
            const {pathname, search} = this.history.location;
            const currentUrl = this.url;
            const historyUrl = pathname + search;
            if (currentUrl !== historyUrl) {
                // have to use the historyUrl as a fallback, because currentUrl could be undefined and break the routing
                const url = currentUrl || historyUrl;
                log.info('Router changes URL to "' + url + '"' + (this.redirectFlag ? ' replacing history' : ''));
                this.redirectFlag ? this.history.replace(url) : this.history.push(url);
                this.redirectFlag = false;
            }
        });
    }

    addUpdateRouteHook(hook: UpdateRouteHook) {
        this.updateRouteHooks.push(hook);
    }

    removeUpdateRouteHook(hook: UpdateRouteHook) {
        const hookIndex = this.updateRouteHooks.indexOf(hook);
        if (hookIndex === -1) {
            return;
        }

        this.updateRouteHooks.splice(hookIndex, 1);
    }

    addUpdateAttributesHook(hook: UpdateAttributesHook) {
        this.updateAttributesHooks.push(hook);
    }

    @action bind(key: string, value: IObservableValue<*>, defaultValue: ?string | number | boolean = undefined) {
        if (key in this.attributes && value.get() !== this.attributes[key]) {
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

    reload = () => {
        this.match(this.history.location.pathname, this.history.location.search);
    };

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
                attributes[names[i - 1].name] = Router.tryParse(match[i]);
            }

            const search = new URLSearchParams(queryString);
            search.forEach((value, key) => {
                attributes[key] = Router.tryParse(value);
            });

            this.handleNavigation(name, attributes, this.navigate);

            break;
        }
    }

    handleNavigation(name: string, attributes: Object, updateRouteMethod: UpdateRouteMethod): void {
        if (!this.isRouteChanging(name, attributes)) {
            return;
        }

        this.createAttributesHistory();
        this.update(name, attributes, updateRouteMethod);
    }

    @action navigate = (name: string, attributes: Object = {}): void => {
        this.clearBindings();
        this.handleNavigation(name, attributes, this.navigate);
    };

    @action redirect = (name: string, attributes: Object = {}): void => {
        this.redirectFlag = true;
        this.clearBindings();
        this.handleNavigation(name, attributes, this.redirect);
    };

    restore = (name: string, attributes: Object = {}): void => {
        if (!this.attributesHistory[name] || this.attributesHistory[name].length === 0) {
            this.update(name, attributes, this.restore);
            return;
        }

        if (!this.isRouteChanging(name, attributes)) {
            return;
        }

        const attributesHistory = this.attributesHistory[name].pop();

        this.update(name, {...attributesHistory, ...attributes}, this.restore);
    };

    @action update(name: string, attributes: Object, updateRouteMethod: UpdateRouteMethod): void {
        const route = routeRegistry.get(name);

        const updatedAttributes = {
            ...this.updateAttributesHooks.reduce((hookAttributes, updateAttributeHook) => ({
                ...updateAttributeHook(route, attributes),
                ...hookAttributes,
            }), {}),
            ...attributes,
        };

        const attributeDefaults = route.attributeDefaults;
        Object.keys(attributeDefaults).forEach((key) => {
            // set default attributes if not passed, to automatically set important omitted attributes everywhere
            // e.g. allows to always pass the default locale if nothing is passed
            if (updatedAttributes[key] !== undefined) {
                return;
            }
            updatedAttributes[key] = attributeDefaults[key];
        });

        for (const updateRouteHook of this.updateRouteHooks) {
            if (!updateRouteHook(route, updatedAttributes, updateRouteMethod)) {
                return;
            }
        }

        this.route = route;
        this.attributes = updatedAttributes;

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

    static tryParse(value: string) {
        if (value === 'true') {
            return true;
        }

        if (value === 'false') {
            return false;
        }

        if (isNaN(value)) {
            return value;
        }

        return parseFloat(value);
    }
}
