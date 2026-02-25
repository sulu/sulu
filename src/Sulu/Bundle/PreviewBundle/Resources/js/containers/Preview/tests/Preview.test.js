// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Router from 'sulu-admin-bundle/services/Router';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import {webspaceStore} from 'sulu-page-bundle/stores';
import PreviewStore from '../stores/PreviewStore';
import Preview from '../Preview';

window.open = jest.fn().mockReturnValue({addEventListener: jest.fn()});

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

jest.mock('debounce', () => jest.fn((callback) => {
    callback.clear = jest.fn();
    return callback;
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
            id: resourceStore.id,
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
            id,
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

function renderPreview(formStore: any, router: any) {
    const previewRef: any = React.createRef();
    const view = render(<Preview formStore={formStore} ref={previewRef} router={router} />);

    if (!previewRef.current) {
        throw new Error('Preview ref was not set');
    }

    return {previewRef, ...view};
}

async function startPreview(previewRef: any) {
    const startPromise = Promise.resolve();
    const previewStore = previewRef.current.previewStore;

    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    act(() => {
        previewRef.current.handleStartClick();
    });

    await startPromise;

    return previewStore;
}

beforeEach(() => {
    jest.resetModules();

    Preview.mode = 'on_request';
    Preview.audienceTargeting = false;

    webspaceStore.getWebspace.mockReturnValue({segments: []});
});

test('Render correct preview', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {previewRef, container} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);

    expect(previewStore.resourceKey).toBe('pages');
    expect(container).not.toBeEmptyDOMElement();
});

test('Render correct preview use route option for resourceKey', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});
    router.route.options.previewResourceKey = 'page_contents';

    const {previewRef} = renderPreview(formStore, router);

    const previewStore = previewRef.current.previewStore;
    expect(previewStore.resourceKey).toBe('page_contents');
});

test('Render correct preview with target groups', async() => {
    const targetGroupsPromise = Promise.resolve({_embedded: {target_groups: []}});
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;

    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);

    expect(previewStore.start).toBeCalled();
    expect(ResourceRequester.getList).toBeCalled();
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {getByRole} = renderPreview(formStore, router);

    expect(getByRole('button', {name: 'Start'})).toBeInTheDocument();
});

test('Render nothing if separate window is opened and rerender if it is closed', async() => {
    const previewWindow = {addEventListener: jest.fn()};
    window.open.mockReturnValue(previewWindow);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {previewRef, container} = renderPreview(formStore, router);

    await startPreview(previewRef);

    expect(container).not.toBeEmptyDOMElement();

    act(() => {
        previewRef.current.handlePreviewWindowClick();
    });

    expect(container).toBeEmptyDOMElement();
    expect(previewWindow.addEventListener).toBeCalledWith('beforeunload', expect.anything());

    act(() => {
        getMockCallArg(previewWindow.addEventListener, 0, 1)();
    });

    expect(container).not.toBeEmptyDOMElement();
});

test('Change css class when selection of device has changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {previewRef, container} = renderPreview(formStore, router);

    await startPreview(previewRef);

    expect(container.querySelector('.auto')).toBeInTheDocument();
    expect(container.querySelector('.desktop')).toBeNull();
    expect(container.querySelector('.tablet')).toBeNull();
    expect(container.querySelector('.smartphone')).toBeNull();

    act(() => {
        previewRef.current.handleDeviceSelectChange('tablet');
    });

    expect(container.querySelector('.auto')).toBeNull();
    expect(container.querySelector('.desktop')).toBeNull();
    expect(container.querySelector('.tablet')).toBeInTheDocument();
    expect(container.querySelector('.smartphone')).toBeNull();

    act(() => {
        previewRef.current.handleDeviceSelectChange('desktop');
    });

    expect(container.querySelector('.auto')).toBeNull();
    expect(container.querySelector('.desktop')).toBeInTheDocument();
    expect(container.querySelector('.tablet')).toBeNull();
    expect(container.querySelector('.smartphone')).toBeNull();

    act(() => {
        previewRef.current.handleDeviceSelectChange('smartphone');
    });

    expect(container.querySelector('.auto')).toBeNull();
    expect(container.querySelector('.desktop')).toBeNull();
    expect(container.querySelector('.tablet')).toBeNull();
    expect(container.querySelector('.smartphone')).toBeInTheDocument();
});

test('Change webspace in PreviewStore when selection of webspace has changed', async() => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);

    expect(PreviewStore).toBeCalledWith('pages', 1, locale, 'sulu_io', undefined);

    act(() => {
        previewRef.current.handleWebspaceChange('example');
    });

    expect(previewStore.setWebspace).toBeCalledWith('example');
});

test('Use router attribute to determine webspace', () => {
    const locale = observable.box('ru');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});
    router.attributes.webspace = 'example';

    renderPreview(formStore, router);

    expect(PreviewStore).toBeCalledWith('pages', 1, locale, 'example', undefined);
});

test('Change segment in PreviewStore when selection of segment has changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);

    expect(PreviewStore).toBeCalledWith('pages', 1, undefined, 'sulu_io', 'w');

    act(() => {
        previewRef.current.handleSegmentChange('s');
    });

    expect(previewStore.setSegment).toBeCalledWith('s');
});

test('React and update preview when data is changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');

    formStore.data = observable.map({title: 'Test'});
    formStore.loading = false;
    formStore.type = observable.box('default');

    const router = new Router({});
    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);
    previewStore.update.mockReturnValue(Promise.resolve('<h1>Sulu is awesome</h1>'));
    previewStore.token = '123-123-123';

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    expect(previewStore.update).toBeCalledWith({title: 'New Test'});
});

test('React and update preview in external window when data is changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');

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

    formStore.data = observable.map({title: 'Test'});
    formStore.loading = false;
    formStore.type = observable.box('default');

    const router = new Router({});
    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.token = '123-123-123';

    act(() => {
        previewRef.current.handlePreviewWindowClick();
    });

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    await updatePromise;

    expect(previewStore.update).toBeCalledWith({title: 'New Test'});
    expect(previewWindow.document.open).toBeCalledWith();
    expect(previewWindow.document.write).toBeCalledWith('<h1>Sulu is awesome</h1>');
    expect(previewWindow.document.close).toBeCalledWith();
});

test('Dont react or update preview when data is changed during formstore is loading', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');

    formStore.data = observable.map({title: 'Test'});
    formStore.loading = true;
    formStore.type = observable.box('default');

    const router = new Router({});
    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);
    previewStore.update.mockReturnValue(Promise.resolve('<h1>Sulu is awesome</h1>'));

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    expect(previewStore.update).not.toBeCalled();
});

test('Dont react or update preview when data is changed during preview-store is starting', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');

    formStore.data = observable.map({title: 'Test'});
    formStore.loading = false;
    formStore.type = observable.box('default');

    const router = new Router({});
    const {previewRef} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = previewRef.current.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(Promise.resolve('<h1>Sulu is awesome</h1>'));
    previewStore.starting = true;

    act(() => {
        previewRef.current.handleStartClick();
    });

    act(() => {
        formStore.data.set('title', 'New Test');
    });

    await startPromise;

    expect(previewStore.update).not.toBeCalled();
});

test('React and update-context when schema is changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');

    formStore.data = observable.map({title: 'Test'});
    formStore.loading = false;
    formStore.type = observable.box('default');
    formStore.schema = observable.box({title: {label: 'Title'}});

    const router = new Router({});
    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);
    previewStore.updateContext.mockReturnValue(Promise.resolve('<h1>Sulu is awesome</h1>'));

    formStore.type.set('homepage');
    formStore.schema.set({title: {label: 'Title', colSpan: 12}});

    expect(previewStore.updateContext).toBeCalledWith('homepage', {title: 'Test'});
});

test('React and restart when locale is changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');

    formStore.data = observable.map({title: 'Test'});
    formStore.loading = false;
    formStore.type = observable.box('default');
    formStore.schema = observable.box({title: {label: 'Title'}});
    formStore.locale = observable.box('en');

    const router = new Router({});
    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);

    formStore.type.set('homepage');
    formStore.locale.set('de');

    expect(previewStore.restart).toBeCalled();
});

test('Change target group in PreviewStore when selection of target group has changed', async() => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;

    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);
    previewStore.token = '123-123-123';

    expect(PreviewStore).toBeCalledWith('pages', 1, locale, 'sulu_io', undefined);

    act(() => {
        previewRef.current.handleTargetGroupChange(4);
    });

    expect(previewStore.setTargetGroup).toBeCalledWith(4);
    expect(previewStore.update).toBeCalledWith({});
});

test('Change dateTime in PreviewStore when DatePicker changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    const {previewRef} = renderPreview(formStore, router);

    const previewStore = await startPreview(previewRef);

    expect(PreviewStore).toBeCalledWith('pages', 1, undefined, 'sulu_io', undefined);

    const date = new Date();

    act(() => {
        previewRef.current.handleDateTimeChange(date);
    });

    expect(previewStore.setDateTime).toBeCalledWith(date);
});

test('Use mainWebspace from formStore data as default webspace', () => {
    const resourceStore = new ResourceStore('articles', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'articles');

    formStore.data = {mainWebspace: 'example'};

    const router = new Router({});

    renderPreview(formStore, router);

    expect(PreviewStore).toBeCalledWith('articles', 1, undefined, 'example', undefined);
});

test('Fall back to first webspace when mainWebspace is not in webspace options', () => {
    const resourceStore = new ResourceStore('articles', 1);
    const formStore: any = new ResourceFormStore(resourceStore, 'articles');

    formStore.data = {mainWebspace: 'non_existent_webspace'};

    const router = new Router({});

    renderPreview(formStore, router);

    expect(PreviewStore).toBeCalledWith('articles', 1, undefined, 'sulu_io', undefined);
});
