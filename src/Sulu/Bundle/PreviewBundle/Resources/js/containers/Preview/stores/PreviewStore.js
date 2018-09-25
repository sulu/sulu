// @flow
import queryString from 'query-string';
import {action, computed, observable} from 'mobx';
import Requester from 'sulu-admin-bundle/services/Requester';
import type {PreviewRouteName} from './../types';

const generateRoute = (name: PreviewRouteName, options: Object): string => {
    const query = queryString.stringify(options);
    if (query.length === 0) {
        return PreviewStore.routes[name];
    }

    return PreviewStore.routes[name] + '?' + query;
};

export default class PreviewStore {
    static routes: {[PreviewRouteName]: string} = {};

    resourceKey: string;
    id: ?string | number;
    locale: string;
    webspace: string;

    @observable token: ?string;

    constructor(resourceKey: string, id: ?string | number, locale: string, webspace: string) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.locale = locale;
        this.webspace = webspace;
    }

    @computed get starting() {
        return !this.token;
    }

    @computed get renderRoute() {
        return generateRoute('render', {
            webspace: this.webspace,
            locale: this.locale,
            token: this.token,
        });
    }

    @action setToken = (token: ?string) => {
        this.token = token;
    };

    start(): Promise<*> {
        const route = generateRoute('start', {
            provider: this.resourceKey,
            locale: this.locale,
            id: this.id,
        });

        return Requester.get(route).then((response) => {
            this.setToken(response.token);
        });
    }

    update(data: Object): Promise<string> {
        const route = generateRoute('update', {
            locale: this.locale,
            webspace: this.webspace,
            token: this.token,
        });

        return Requester.post(route, {data: data}).then((response) => {
            return response.content;
        });
    }

    updateContext(type: string): Promise<string> {
        const route = generateRoute('update-context', {
            webspace: this.webspace,
            token: this.token,
        });

        return Requester.post(route, {context: {template: type}}).then((response) => {
            return response.content;
        });
    }

    stop(): Promise<*> {
        const route = generateRoute('stop', {token: this.token});

        return Requester.get(route).then(this.setToken(null));
    }
}
