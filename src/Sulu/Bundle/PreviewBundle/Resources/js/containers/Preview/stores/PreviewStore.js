// @flow
import {action, computed, observable} from 'mobx';
import {Requester} from 'sulu-admin-bundle/services';
import {buildQueryString, transformDateForUrl} from 'sulu-admin-bundle/utils';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {PreviewRouteName} from './../types';

const generateRoute = (name: PreviewRouteName, options: Object): string => {
    return PreviewStore.endpoints[name] + buildQueryString(options);
};

export default class PreviewStore {
    static endpoints: {[PreviewRouteName]: string} = {};

    resourceKey: string;
    id: ?string | number;
    locale: ?IObservableValue<string>;
    @observable webspace: string;
    @observable segment: ?string;
    @observable targetGroup: number = -1;
    @observable dateTime: ?Date = undefined;

    @observable token: ?string;

    constructor(
        resourceKey: string,
        id: ?string | number,
        locale: ?IObservableValue<string>,
        webspace: string,
        segment: ?string
    ) {
        // keep backwards compatibility to previous versions where locale was passed as string
        if (typeof locale === 'string') {
            locale = observable.box(locale);
        }
        this.resourceKey = resourceKey;
        this.id = id;
        this.locale = locale;
        this.webspace = webspace;
        this.segment = segment;
    }

    @computed get starting() {
        return !this.token;
    }

    @computed get renderRoute() {
        return generateRoute('render', {
            webspaceKey: this.webspace,
            segmentKey: this.segment,
            provider: this.resourceKey,
            id: this.id,
            locale: this.locale,
            token: this.token,
            targetGroupId: this.targetGroup,
            dateTime: this.dateTime && transformDateForUrl(this.dateTime),
        });
    }

    @action setToken = (token: ?string) => {
        this.token = token;
    };

    @action setWebspace = (webspace: string) => {
        this.webspace = webspace;
    };

    @action setTargetGroup = (targetGroup: number) => {
        this.targetGroup = targetGroup;
    };

    @action setSegment = (segment: ?string) => {
        this.segment = segment;
    };

    @action setDateTime = (dateTime: ?Date) => {
        this.dateTime = dateTime;
    };

    start(): Promise<*> {
        const route = generateRoute('start', {
            provider: this.resourceKey,
            id: this.id,
            locale: this.locale,
        });

        return Requester.post(route).then((response) => {
            this.setToken(response.token);
        });
    }

    restart(): Promise<string> {
        return this.stop().then(() => this.start());
    }

    update(data: Object): Promise<string> {
        const route = generateRoute('update', {
            locale: this.locale,
            webspaceKey: this.webspace,
            segmentKey: this.segment,
            token: this.token,
            provider: this.resourceKey,
            id: this.id,
            targetGroupId: this.targetGroup,
            dateTime: this.dateTime && transformDateForUrl(this.dateTime),
        });

        return Requester.post(route, {data}).then((response) => {
            return response.content;
        });
    }

    updateContext(type: string, data: Object): Promise<string> {
        const route = generateRoute('update-context', {
            webspaceKey: this.webspace,
            segmentKey: this.segment,
            token: this.token,
            locale: this.locale,
            provider: this.resourceKey,
            id: this.id,
            targetGroupId: this.targetGroup,
            dateTime: this.dateTime && transformDateForUrl(this.dateTime),
        });

        return Requester.post(route, {data, context: {template: type}}).then((response) => {
            return response.content;
        });
    }

    stop(): Promise<*> {
        const route = generateRoute('stop', {token: this.token});

        return Requester.post(route).then(() => this.setToken(null));
    }
}
