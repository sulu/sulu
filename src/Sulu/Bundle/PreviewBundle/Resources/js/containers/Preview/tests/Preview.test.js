// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router from 'sulu-admin-bundle/services/Router';
import Preview from '../Preview';

jest.mock('debounce', () => jest.fn((value) => value));

jest.mock('../stores/PreviewStore', () => jest.fn(function() {
    this.start = jest.fn().mockReturnValue(Promise.resolve());
    this.update = jest.fn().mockReturnValue(Promise.resolve());
    this.updateContext = jest.fn().mockReturnValue(Promise.resolve());
    this.stop = jest.fn().mockReturnValue(Promise.resolve());

    this.renderRoute = '/render';
}));

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    get: jest.fn().mockImplementation((route: string) => new Promise((resolve) => {
        if (route === '/start') {
            resolve({token: '123-123-123'});
        }
    })),
    post: jest.fn().mockReturnValue(Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function(history) {
    this.history = history;
    this.attributes = {locale: 'de'};
}));

beforeEach(() => {
    jest.resetModules();

    Preview.mode = 'on_request';
});

test('Render correct preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const component = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = component.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    component.instance().handleStartClick();

    startPromise.then(() => {
        expect(component).toMatchSnapshot();
    });
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    expect(render(<Preview formStore={formStore} router={router} />)).toMatchSnapshot();
});

test('React and update preview when data is changed', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const component = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = component.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    component.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).toBeCalledWith({title: 'New Test'});

        expect(component).toMatchSnapshot();
    });
});

test('Dont react or update preview when data is changed during formstore is loading', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = true;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const component = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = component.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    component.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).not.toBeCalled();

        expect(component).toMatchSnapshot();
    });
});

test('Dont react or update preview when data is changed during preview-store is starting', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const component = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = component.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = true;

    component.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).not.toBeCalled();

        expect(component).toMatchSnapshot();
    });
});

test('React and update-context when type is changed', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const component = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = component.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    component.instance().handleStartClick();

    // $FlowFixMe
    formStore.type.set('homepage');

    return startPromise.then(() => {
        expect(previewStore.updateContext).toBeCalledWith('homepage');
    });
});
