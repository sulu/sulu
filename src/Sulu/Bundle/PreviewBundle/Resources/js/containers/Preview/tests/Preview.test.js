// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import {observable} from 'mobx';
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

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

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

const renderPreview = (formStore, router) => {
    const previewRef: any = React.createRef();
    const view = render(<Preview formStore={formStore} ref={previewRef} router={router} />);

    const getPreview = () => {
        if (!previewRef.current) {
            throw new Error('Expected Preview instance ref to be set');
        }

        return previewRef.current;
    };

    return {
        ...view,
        getPreview,
    };
};

const startPreview = async(preview, startPromise: Promise<mixed>) => {
    await act(async() => {
        preview.handleStartClick();
        await startPromise;
    });
};

beforeEach(() => {
    jest.resetModules();

    Preview.mode = 'on_request';
    Preview.audienceTargeting = false;

    webspaceStore.getWebspace.mockReturnValue({segments: []});
});

test('Render correct preview', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {asFragment, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    expect(previewStore.resourceKey).toBe('pages');
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(asFragment()).toMatchSnapshot();
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

    const {getPreview} = renderPreview(formStore, router);

    const previewStore = getPreview().previewStore;
    expect(previewStore.resourceKey).toBe('page_contents');
});

test('Render correct preview with target groups', async() => {
    const targetGroupsPromise = Promise.resolve({_embedded: {target_groups: []}});
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;
    const {asFragment, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {asFragment} = renderPreview(formStore, router);

    expect(screen.getByRole('button', {name: 'Start'})).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Render nothing if separate window is opened and rerender if it is closed', async() => {
    const previewWindow = {addEventListener: jest.fn()};
    window.open.mockReturnValue(previewWindow);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {asFragment, container, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(asFragment()).toMatchSnapshot();

    act(() => {
        preview.handlePreviewWindowClick();
    });

    expect(container).toBeEmptyDOMElement();
    expect(previewWindow.addEventListener).toBeCalledWith('beforeunload', expect.anything());

    act(() => {
        previewWindow.addEventListener.mock.calls[0][1]();
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Change css class when selection of device has changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {container, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(container.querySelectorAll('.auto')).toHaveLength(1);
    expect(container.querySelectorAll('.desktop')).toHaveLength(0);
    expect(container.querySelectorAll('.tablet')).toHaveLength(0);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

    act(() => {
        preview.handleDeviceSelectChange('tablet');
    });

    expect(container.querySelectorAll('.auto')).toHaveLength(0);
    expect(container.querySelectorAll('.desktop')).toHaveLength(0);
    expect(container.querySelectorAll('.tablet')).toHaveLength(1);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

    act(() => {
        preview.handleDeviceSelectChange('desktop');
    });

    expect(container.querySelectorAll('.auto')).toHaveLength(0);
    expect(container.querySelectorAll('.desktop')).toHaveLength(1);
    expect(container.querySelectorAll('.tablet')).toHaveLength(0);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

    act(() => {
        preview.handleDeviceSelectChange('smartphone');
    });

    expect(container.querySelectorAll('.auto')).toHaveLength(0);
    expect(container.querySelectorAll('.desktop')).toHaveLength(0);
    expect(container.querySelectorAll('.tablet')).toHaveLength(0);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(1);
});

test('Change webspace in PreviewStore when selection of webspace has changed', async() => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'sulu_io', undefined);

    act(() => {
        preview.handleWebspaceChange('example');
    });

    expect(previewStore.setWebspace).toBeCalledWith('example');
});

test('Use router attribute to determine webspace', async() => {
    const locale = observable.box('ru');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});
    router.attributes.webspace = 'example';

    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'example', undefined);
});

test('Change segment in PreviewStore when selection of segment has changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, undefined, 'sulu_io', 'w');

    act(() => {
        preview.handleSegmentChange('s');
    });

    expect(previewStore.setSegment).toBeCalledWith('s');
});

test('React and update preview when data is changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const {asFragment, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    await startPreview(preview, startPromise);

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    await act(async() => {
        await updatePromise;
    });

    previewStore.token = '123-123-123';
    expect(previewStore.update).toBeCalledWith({title: 'New Test'});

    expect(asFragment()).toMatchSnapshot();
});

test('React and update preview in external window when data is changed', async() => {
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
        scrollTo: jest.fn(),
    };
    window.open.mockReturnValue(previewWindow);

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const {asFragment, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    await startPreview(preview, startPromise);

    act(() => {
        preview.handlePreviewWindowClick();
    });

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    await act(async() => {
        await updatePromise;
    });

    expect(previewStore.update).toBeCalledWith({title: 'New Test'});
    expect(asFragment()).toMatchSnapshot();
    expect(previewWindow.document.open).toBeCalledWith();
    expect(previewWindow.document.write).toBeCalledWith('<h1>Sulu is awesome</h1>');
    expect(previewWindow.document.close).toBeCalledWith();
});

test('Dont react or update preview when data is changed during formstore is loading', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = true;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const {asFragment, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    expect(previewStore.update).not.toBeCalled();
    expect(asFragment()).toMatchSnapshot();
});

test('Dont react or update preview when data is changed during preview-store is starting', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = new Router({});
    const {asFragment, getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = true;

    await startPreview(preview, startPromise);

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    expect(previewStore.update).not.toBeCalled();
    expect(asFragment()).toMatchSnapshot();
});

test('React and update-context when schema is changed', async() => {
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
    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    act(() => {
        // $FlowFixMe
        formStore.type.set('homepage');
        // $FlowFixMe
        formStore.schema.set({title: {label: 'Title', colSpan: 12}});
    });

    expect(previewStore.updateContext).toBeCalledWith('homepage', {title: 'Test'});
});

test('React and restart when locale is changed', async() => {
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
    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    act(() => {
        // $FlowFixMe
        formStore.type.set('homepage');
        // $FlowFixMe
        formStore.locale.set('de');
    });

    expect(previewStore.restart).toBeCalled();
});

test('Change target group in PreviewStore when selection of target group has changed', async() => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;

    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    await startPreview(preview, startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'sulu_io', undefined);

    act(() => {
        preview.handleTargetGroupChange(4);
    });

    expect(previewStore.setTargetGroup).toBeCalledWith(4);
    expect(previewStore.update).toBeCalledWith(undefined);
});

test('Change dateTime in PreviewStore when DatePicker changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {getPreview} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const preview = getPreview();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(preview, startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, undefined, 'sulu_io', undefined);

    const date = new Date();
    act(() => {
        preview.handleDateTimeChange(date);
    });

    expect(previewStore.setDateTime).toBeCalledWith(date);
});
