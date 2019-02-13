// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router from 'sulu-admin-bundle/services/Router';
import Preview from '../Preview';

window.open = jest.fn().mockReturnValue({addEventListener: jest.fn()});

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

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    jest.resetModules();

    Preview.mode = 'on_request';
});

test('Render correct preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(preview).toMatchSnapshot();
    });
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    expect(render(<Preview formStore={formStore} router={router} />)).toMatchSnapshot();
});

test('Render nothing if separate window is opened and rerender if it is closed', () => {
    const previewWindow = {addEventListener: jest.fn()};
    window.open.mockReturnValue(previewWindow);

    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(preview).toMatchSnapshot();
        preview.find('Button[icon="su-link"]').simulate('click');
        expect(preview.html()).toEqual(null);

        expect(previewWindow.addEventListener).toBeCalledWith('beforeunload', expect.anything());
        previewWindow.addEventListener.mock.calls[0][1]();
        expect(preview).toMatchSnapshot();
    });
});

test('Change css class when selection of device has changed', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(preview.find('.auto')).toHaveLength(1);
        expect(preview.find('.desktop')).toHaveLength(0);
        expect(preview.find('.tablet')).toHaveLength(0);
        expect(preview.find('.smartphone')).toHaveLength(0);

        preview.find('Select').prop('onChange')('tablet');
        expect(preview.find('.auto')).toHaveLength(0);
        expect(preview.find('.desktop')).toHaveLength(0);
        expect(preview.find('.tablet')).toHaveLength(1);
        expect(preview.find('.smartphone')).toHaveLength(0);

        preview.find('Select').prop('onChange')('desktop');
        expect(preview.find('.auto')).toHaveLength(0);
        expect(preview.find('.desktop')).toHaveLength(1);
        expect(preview.find('.tablet')).toHaveLength(0);
        expect(preview.find('.smartphone')).toHaveLength(0);

        preview.find('Select').prop('onChange')('smartphone');
        expect(preview.find('.auto')).toHaveLength(0);
        expect(preview.find('.desktop')).toHaveLength(0);
        expect(preview.find('.tablet')).toHaveLength(0);
        expect(preview.find('.smartphone')).toHaveLength(1);
    });
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
    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).toBeCalledWith({title: 'New Test'});

        expect(preview).toMatchSnapshot();
    });
});

test('React and update preview in external window when data is changed', () => {
    const resourceStore = new ResourceStore('pages', 1, {title: 'Test'});
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    const previewWindow = {
        addEventListener: jest.fn(),
        document: {
            close: jest.fn(),
            open: jest.fn(),
            write: jest.fn(),
        },
    };
    window.open.mockReturnValue(previewWindow);

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();
    preview.update();
    preview.find('Button[icon="su-link"]').prop('onClick')();
    preview.update();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).toBeCalledWith({title: 'New Test'});

        expect(preview).toMatchSnapshot();
        expect(previewWindow.document.open).toBeCalledWith();
        expect(previewWindow.document.write).toBeCalledWith('<h1>Sulu is awesome</h1>');
        expect(previewWindow.document.close).toBeCalledWith();
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
    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).not.toBeCalled();

        expect(preview).toMatchSnapshot();
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
    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = true;

    preview.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        expect(previewStore.update).not.toBeCalled();

        expect(preview).toMatchSnapshot();
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
    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    // $FlowFixMe
    formStore.type.set('homepage');

    return startPromise.then(() => {
        expect(previewStore.updateContext).toBeCalledWith('homepage');
    });
});
