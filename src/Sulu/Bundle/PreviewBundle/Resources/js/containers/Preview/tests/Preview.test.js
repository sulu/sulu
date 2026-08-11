// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import log from 'loglevel';
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
window.requestAnimationFrame = (callback) => callback();

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
            data: {},
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

        expect(previewWindow.addEventListener).toHaveBeenCalledWith('beforeunload', expect.anything());
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
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'sulu_io', undefined);

        preview.find('Select').at(1).prop('onChange')('example');
        expect(previewStore.setWebspace).toHaveBeenCalledWith('example');
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
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'example', undefined);
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
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, undefined, 'sulu_io', 'w');

        preview.find('Select').at(2).prop('onChange')('s');
        expect(previewStore.setSegment).toHaveBeenCalledWith('s');
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
        expect(previewStore.update).toHaveBeenCalledWith({title: 'New Test'});

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
        expect(previewStore.update).toHaveBeenCalledWith({title: 'New Test'});

        expect(preview.render()).toMatchSnapshot();
        expect(previewWindow.document.open).toHaveBeenCalledWith();
        expect(previewWindow.document.write).toHaveBeenCalledWith('<h1>Sulu is awesome</h1>');
        expect(previewWindow.document.close).toHaveBeenCalledWith();
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
        expect(previewStore.update).not.toHaveBeenCalled();

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
        expect(previewStore.update).not.toHaveBeenCalled();

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
        expect(previewStore.updateContext).toHaveBeenCalledWith('homepage', {title: 'Test'});
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
        expect(previewStore.restart).toHaveBeenCalled();
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
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'sulu_io', undefined);

        preview.find('Select').at(2).prop('onChange')(4);
        expect(previewStore.setTargetGroup).toHaveBeenCalledWith(4);
        expect(previewStore.update).toHaveBeenCalledWith({});
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
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, undefined, 'sulu_io', undefined);

        const date = new Date();
        preview.find('Button[icon="su-calendar"]').simulate('click');
        preview.find('DatePicker').prop('onChange')(date);
        expect(previewStore.setDateTime).toHaveBeenCalledWith(date);
    });
});

test('Use mainWebspace from formStore data as default webspace', () => {
    const resourceStore = new ResourceStore('articles', 1);
    const formStore = new ResourceFormStore(resourceStore, 'articles');

    // $FlowFixMe
    formStore.data = {mainWebspace: 'example'};

    const router = new Router({});

    mount(<Preview formStore={formStore} router={router} />);

    expect(PreviewStore).toHaveBeenCalledWith('articles', undefined, undefined, 'example', undefined);
});

test('Fall back to first webspace when mainWebspace is not in webspace options', () => {
    const resourceStore = new ResourceStore('articles', 1);
    const formStore = new ResourceFormStore(resourceStore, 'articles');

    // $FlowFixMe
    formStore.data = {mainWebspace: 'non_existent_webspace'};

    const router = new Router({});

    mount(<Preview formStore={formStore} router={router} />);

    expect(PreviewStore).toHaveBeenCalledWith('articles', undefined, undefined, 'sulu_io', undefined);
});

test('Use mainWebspace when the current locale is a nested localization', () => {
    const locale = observable.box('de_ch');
    const resourceStore = new ResourceStore('articles', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'articles');

    // $FlowFixMe
    formStore.data = {mainWebspace: 'example'};

    const grantedWebspaces = webspaceStore.grantedWebspaces;
    // $FlowFixMe
    webspaceStore.grantedWebspaces = [
        {
            key: 'example',
            name: 'Example',
            localizations: [
                {locale: 'de', children: [{locale: 'de_ch'}, {locale: 'de_de'}]},
                {locale: 'en'},
            ],
        },
    ];

    const router = new Router({});

    try {
        mount(<Preview formStore={formStore} router={router} />);

        expect(PreviewStore).toHaveBeenCalledWith('articles', undefined, locale, 'example', undefined);
    } finally {
        // $FlowFixMe
        webspaceStore.grantedWebspaces = grantedWebspaces;
    }
});

test('Scroll to and expand a block referenced by a preview navigate click, mounting nested ' +
    'content on demand like a real collapsed block would', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'parent-id', items: [{_id: 'child-id'}]}]};
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const body = document.body;
    if (!body) {
        throw new Error('Expected document body');
    }

    const parent = document.createElement('section');
    parent.setAttribute('data-sulu-block-id', 'parent-id');
    body.appendChild(parent);

    // A collapsed block only renders its collapsed preview, not its nested fields (see
    // FieldBlocks.js), so "child" does not exist in the DOM until "parent" is expanded - mimic
    // that here by only mounting it once the parent is clicked.
    const child = document.createElement('section');
    child.setAttribute('data-sulu-block-id', 'child-id');
    // $FlowFixMe
    child.scrollIntoView = jest.fn();
    const handleParentClick = jest.fn(() => parent.appendChild(child));
    const handleChildClick = jest.fn();
    parent.addEventListener('click', handleParentClick);
    child.addEventListener('click', handleChildClick);

    preview.instance().navigateToBlock('child-id');

    expect(parent.contains(child)).toBe(true);
    expect(handleParentClick).toHaveBeenCalled();
    // The target itself must also be clicked/expanded, not just scrolled to - it may be
    // collapsed too, and scrolling to a collapsed block would show nothing useful.
    expect(handleChildClick).toHaveBeenCalled();
    expect(child.scrollIntoView).toHaveBeenCalledWith({behavior: 'smooth', block: 'start'});

    body.removeChild(parent);
});

test('Expands the target block itself (not just its ancestors) for a top-level, non-nested block', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'block-1'}]};
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const body = document.body;
    if (!body) {
        throw new Error('Expected document body');
    }

    const block = document.createElement('section');
    block.setAttribute('data-sulu-block-id', 'block-1');
    // $FlowFixMe
    block.scrollIntoView = jest.fn();
    const handleClick = jest.fn();
    block.addEventListener('click', handleClick);
    body.appendChild(block);

    preview.instance().navigateToBlock('block-1');

    expect(handleClick).toHaveBeenCalled();
    expect(block.scrollIntoView).toHaveBeenCalledWith({behavior: 'smooth', block: 'start'});

    body.removeChild(block);
});

test('Does nothing when the referenced block is not present in the form data', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    expect(() => preview.instance().navigateToBlock('unknown-id')).not.toThrow();
});

test('Gives up instead of polling forever when an ancestor never mounts its nested content', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'parent-id', items: [{_id: 'child-id'}]}]};
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);

    const body = document.body;
    if (!body) {
        throw new Error('Expected document body');
    }

    const parent = document.createElement('section');
    parent.setAttribute('data-sulu-block-id', 'parent-id');
    body.appendChild(parent);
    // "child" is intentionally never appended, simulating a block that never mounts.

    expect(() => preview.instance().navigateToBlock('child-id')).not.toThrow();

    body.removeChild(parent);
});

test('Reacts to a sulu.preview.navigate message originating from its own preview window', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);
    const instance = preview.instance();

    const previewWindow = {};
    jest.spyOn(instance, 'getPreviewWindow').mockReturnValue(previewWindow);
    jest.spyOn(instance, 'navigateToBlock').mockImplementation(() => {});

    // $FlowFixMe
    instance.handleMessage({
        source: previewWindow,
        origin: window.location.origin,
        data: {type: 'sulu.preview.navigate', id: 'block-1'},
    });

    expect(instance.navigateToBlock).toHaveBeenCalledWith('block-1');
});

test('Ignores messages that do not originate from its own preview window', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);
    const instance = preview.instance();

    jest.spyOn(instance, 'getPreviewWindow').mockReturnValue({});
    jest.spyOn(instance, 'navigateToBlock').mockImplementation(() => {});

    // $FlowFixMe
    instance.handleMessage({
        source: {},
        origin: window.location.origin,
        data: {type: 'sulu.preview.navigate', id: 'block-1'},
    });

    expect(instance.navigateToBlock).not.toHaveBeenCalled();
});

test('Ignores messages whose source matches but whose origin does not (source survives ' +
    'cross-origin navigation of the preview window)', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);
    const instance = preview.instance();

    const previewWindow = {};
    jest.spyOn(instance, 'getPreviewWindow').mockReturnValue(previewWindow);
    jest.spyOn(instance, 'navigateToBlock').mockImplementation(() => {});

    // $FlowFixMe
    instance.handleMessage({
        source: previewWindow,
        origin: 'https://attacker.example',
        data: {type: 'sulu.preview.navigate', id: 'block-1'},
    });

    expect(instance.navigateToBlock).not.toHaveBeenCalled();
});

test('Warns about blocks missing the preview deep-link attribute', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {
        blocks: [
            {_id: 'block-1', type: 'text'},
            {_id: 'block-2', type: 'text'},
        ],
    };
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);
    const warnSpy = jest.spyOn(log, 'warn').mockImplementation(() => {});

    preview.instance().warnAboutMissingDeepLinkAttributes(['block-1']);

    expect(warnSpy).toHaveBeenCalledWith(expect.stringContaining('block-2'));

    warnSpy.mockRestore();
});

test('Does not warn when every block carries the preview deep-link attribute', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {
        blocks: [{_id: 'block-1', type: 'text'}],
    };
    const router = new Router({});

    const preview = shallow(<Preview formStore={formStore} router={router} />);
    const warnSpy = jest.spyOn(log, 'warn').mockImplementation(() => {});

    preview.instance().warnAboutMissingDeepLinkAttributes(['block-1']);

    expect(warnSpy).not.toHaveBeenCalled();

    warnSpy.mockRestore();
});
