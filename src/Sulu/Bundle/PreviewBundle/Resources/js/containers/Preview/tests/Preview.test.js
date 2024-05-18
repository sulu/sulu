// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router, {Route} from 'sulu-admin-bundle/services/Router';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import {webspaceStore} from 'sulu-page-bundle/stores';
import PreviewStore from '../stores/PreviewStore';
import Preview from '../Preview';

window.open = jest.fn().mockReturnValue({addEventListener: jest.fn()});

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

// $FlowFixMe
const constantDate = new Date(2020, 11, 16, 14, 6, 22);

// eslint-disable-next-line no-global-assign
Date = class extends Date {
    constructor() {
        return constantDate;
    }
};

jest.mock('debounce', () => jest.fn((value) => value));

jest.mock('../stores/PreviewStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.restart = jest.fn().mockReturnValue(Promise.resolve());
    this.start = jest.fn().mockReturnValue(Promise.resolve());
    this.update = jest.fn().mockReturnValue(Promise.resolve());
    this.updateContext = jest.fn().mockReturnValue(Promise.resolve());
    this.stop = jest.fn().mockReturnValue(Promise.resolve());
    this.setDateTime = jest.fn();
    this.setSegment = jest.fn();
    this.setWebspace = jest.fn();
    this.setTargetGroup = jest.fn();

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

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(
    (resourceStore) => {
        return {
            resourceKey: resourceStore.resourceKey,
            locale: resourceStore.observableOptions?.locale,
        };
    }
));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(
    (resourceKey, id, observableOptions) => {
        return {
            resourceKey,
            observableOptions,
        };
    }
));

jest.mock('sulu-page-bundle/stores/webspaceStore', () => ({
    grantedWebspaces: [{key: 'sulu_io'}, {key: 'example'}],
    getWebspace: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function(history) {
    this.history = history;
    this.attributes = {};
    this.route = {options: {}};
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    jest.resetModules();

    Preview.mode = 'on_request';
    Preview.audienceTargeting = false;

    webspaceStore.getWebspace.mockReturnValue({segments: []});
});

test('Render correct preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    expect(previewStore.resourceKey).toBe('pages');
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        preview.update();
        expect(preview.render()).toMatchSnapshot();
    });
});

test('Render correct preview use route option for resourceKey', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});
    router.route = new Route({
        path: '/test',
        name: 'test',
        type: 'test',
        options: {
            previewResourceKey: 'page_contents',
        },
    });

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const preview = mount(<Preview formStore={formStore} router={router} />);

    const previewStore = preview.instance().previewStore;
    expect(previewStore.resourceKey).toBe('page_contents');
});

test('Render correct preview with target groups', () => {
    const targetGroupsPromise = Promise.resolve({_embedded: {target_groups: []}});
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;
    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        preview.update();
        expect(preview.render()).toMatchSnapshot();
    });
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = mount(<Preview formStore={formStore} router={router} />);

    expect(preview.render()).toMatchSnapshot();
});

test('Render nothing if separate window is opened and rerender if it is closed', () => {
    const previewWindow = {addEventListener: jest.fn()};
    window.open.mockReturnValue(previewWindow);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(preview.render()).toMatchSnapshot();
        preview.find('Button[icon="su-link"]').simulate('click');
        expect(preview.html()).toEqual(null);

        expect(previewWindow.addEventListener).toBeCalledWith('beforeunload', expect.anything());
        previewWindow.addEventListener.mock.calls[0][1]();
        expect(preview.render()).toMatchSnapshot();
    });
});

test('Change css class when selection of device has changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
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

        preview.find('Select').at(0).prop('onChange')('tablet');
        expect(preview.find('.auto')).toHaveLength(0);
        expect(preview.find('.desktop')).toHaveLength(0);
        expect(preview.find('.tablet')).toHaveLength(1);
        expect(preview.find('.smartphone')).toHaveLength(0);

        preview.find('Select').at(0).prop('onChange')('desktop');
        expect(preview.find('.auto')).toHaveLength(0);
        expect(preview.find('.desktop')).toHaveLength(1);
        expect(preview.find('.tablet')).toHaveLength(0);
        expect(preview.find('.smartphone')).toHaveLength(0);

        preview.find('Select').at(0).prop('onChange')('smartphone');
        expect(preview.find('.auto')).toHaveLength(0);
        expect(preview.find('.desktop')).toHaveLength(0);
        expect(preview.find('.tablet')).toHaveLength(0);
        expect(preview.find('.smartphone')).toHaveLength(1);
    });
});

test('Change webspace in PreviewStore when selection of webspace has changed', () => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'sulu_io', undefined);

        preview.find('Select').at(1).prop('onChange')('example');
        expect(previewStore.setWebspace).toBeCalledWith('example');
    });
});

test('Use router attribute to determine webspace', () => {
    const locale = observable.box('ru');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});
    router.attributes.webspace = 'example';

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'example', undefined);
    });
});

test('Change segment in PreviewStore when selection of segment has changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toBeCalledWith('pages', undefined, undefined, 'sulu_io', 'w');

        preview.find('Select').at(2).prop('onChange')('s');
        expect(previewStore.setSegment).toBeCalledWith('s');
    });
});

test('React and update preview when data is changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
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
    previewStore.token = '123-123-123';

    preview.instance().handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        preview.update();
        previewStore.token = '123-123-123';
        expect(previewStore.update).toBeCalledWith({title: 'New Test'});

        expect(preview.render()).toMatchSnapshot();
    });
});

test('React and update preview in external window when data is changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    const previewWindow = {
        addEventListener: jest.fn(),
        document: {
            close: jest.fn(),
            open: jest.fn(),
            write: jest.fn(),
            document: {
                body: {
                    scrollTop: 10,
                },
            },
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
    previewStore.token = '123-123-123';

    preview.instance().handleStartClick();
    preview.update();
    preview.find('Button[icon="su-link"]').prop('onClick')();
    preview.update();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        preview.update();
        expect(previewStore.update).toBeCalledWith({title: 'New Test'});

        expect(preview.render()).toMatchSnapshot();
        expect(previewWindow.document.open).toBeCalledWith();
        expect(previewWindow.document.write).toBeCalledWith('<h1>Sulu is awesome</h1>');
        expect(previewWindow.document.close).toBeCalledWith();
    });
});

test('Dont react or update preview when data is changed during formstore is loading', () => {
    const resourceStore = new ResourceStore('pages', 1);
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
        preview.update();
        expect(previewStore.update).not.toBeCalled();

        expect(preview.render()).toMatchSnapshot();
    });
});

test('Dont react or update preview when data is changed during preview-store is starting', () => {
    const resourceStore = new ResourceStore('pages', 1);
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
        preview.update();
        expect(previewStore.update).not.toBeCalled();

        expect(preview.render()).toMatchSnapshot();
    });
});

test('React and update-context when schema is changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');
    formStore.schema = observable.box({title: {label: 'Title'}});

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
    // $FlowFixMe
    formStore.schema.set({title: {label: 'Title', colSpan: 12}});

    return startPromise.then(() => {
        expect(previewStore.updateContext).toBeCalledWith('homepage', {title: 'Test'});
    });
});

test('React and restart when locale is changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');
    formStore.schema = observable.box({title: {label: 'Title'}});
    // $FlowFixMe
    formStore.locale = observable.box('en');

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
    // $FlowFixMe
    formStore.locale.set('de');

    return startPromise.then(() => {
        expect(previewStore.restart).toBeCalled();
    });
});

test('Change target group in PreviewStore when selection of target group has changed', () => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'sulu_io', undefined);

        preview.find('Select').at(2).prop('onChange')(4);
        expect(previewStore.setTargetGroup).toBeCalledWith(4);
        expect(previewStore.update).toBeCalledWith(undefined);
    });
});

test('Change dateTime in PreviewStore when DatePicker changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = mount(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.instance().previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.instance().handleStartClick();

    return startPromise.then(() => {
        preview.update();
        expect(PreviewStore).toBeCalledWith('pages', undefined, undefined, 'sulu_io', undefined);

        const date = new Date();
        preview.find('Button[icon="su-calendar"]').simulate('click');
        preview.find('DatePicker').prop('onChange')(date);
        expect(previewStore.setDateTime).toBeCalledWith(date);
    });
});
