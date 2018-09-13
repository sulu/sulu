// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import Requester from 'sulu-admin-bundle/services/Requester';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
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

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id) {
    this.resourceKey = resourceKey;
    this.id = id;
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
    const router = new Router({});

    expect(shallow(<Preview resourceStore={resourceStore} router={router} />)).toMatchSnapshot();
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const router = new Router({});

    previewConfigStore.setConfig({
        mode: 'on_request',
        debounceDelay: 100,
        routes: {},
    });

    expect(render(<Preview resourceStore={resourceStore} router={router} />)).toMatchSnapshot();
});

test('React and update preview when data is changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    resourceStore.data = observable.map({title: 'Test'});
    resourceStore.loading = false;
    const router = new Router({});
    const requestPromise = Promise.resolve({token: '123-123-123'});

    Requester.get.mockReturnValue(requestPromise);

    mount(<Preview resourceStore={resourceStore} router={router} />);

    expect(Requester.get).toBeCalledWith('/start');

    resourceStore.data.set('title', 'New Test');

    return requestPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/update', {data: {title: 'New Test'}});
    });
});
