// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import Requester from 'sulu-admin-bundle/services/Requester';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import FormStore from 'sulu-admin-bundle/containers/Form/stores/FormStore';
import Router from 'sulu-admin-bundle/services/Router';
import Preview from '../Preview';
import type {PreviewRouteName} from '../stores/PreviewConfigStore';
import previewConfigStore from '../stores/PreviewConfigStore';

jest.mock('debounce', () => jest.fn((value) => value));

jest.mock('../stores/PreviewConfigStore', () => ({
    setConfig(config) {
        this.mode = config.mode;
    },
    mode: 'auto',
    generateRoute: (name: PreviewRouteName) => {
        return '/' + name;
    },
}));

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    get: jest.fn().mockImplementation((route: string) => new Promise((resolve) => {
        if (route === '/start') {
            resolve({token: '123-123-123'});
        }
    })),
    post: jest.fn().mockReturnValue(Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/FormStore', () => jest.fn(function() {
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function(history) {
    this.history = history;
    this.attributes = {locale: 'de'};
}));

beforeEach(() => {
    jest.resetModules();

    previewConfigStore.setConfig({
        mode: 'auto',
        debounceDelay: 100,
        routes: {},
    });
});

test('Render correct preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new FormStore(resourceStore);
    const router = new Router({});

    expect(shallow(
        <Preview
            formStore={formStore}
            router={router}
        />
    )).toMatchSnapshot();
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new FormStore(resourceStore);
    const router = new Router({});

    previewConfigStore.setConfig({
        mode: 'on_request',
        debounceDelay: 100,
        routes: {},
    });

    expect(render(
        <Preview
            formStore={formStore}
            router={router}
        />
    )).toMatchSnapshot();
});

test('React and update preview when data is changed', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new FormStore(resourceStore);

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const requestPromise = Promise.resolve({token: '123-123-123'});

    Requester.get.mockReturnValue(requestPromise);

    mount(<Preview formStore={formStore} router={router} />);

    expect(Requester.get).toBeCalledWith('/start');

    formStore.data.set('title', 'New Test');

    return requestPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/update', {data: {title: 'New Test'}});
    });
});

test('React and update-context when type is changed', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new FormStore(resourceStore);

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const requestPromise = Promise.resolve({token: '123-123-123'});

    Requester.get.mockReturnValue(requestPromise);

    mount(<Preview formStore={formStore} router={router} />);

    expect(Requester.get).toBeCalledWith('/start');

    // $FlowFixMe
    formStore.type.set('homepage');

    return requestPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/update-context', {context: {template: 'homepage'}});
    });
});
