// @flow
/* eslint-disable testing-library/no-container */
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

// $FlowFixMe
const constantDate = new Date(2020, 11, 16, 14, 6, 22);

// eslint-disable-next-line no-global-assign
Date = class extends Date {
    constructor() {
        return constantDate;
    }
};

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

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');
    const actualComponents = jest.requireActual('sulu-admin-bundle/components');
    const Toolbar: any = jest.fn((props) => <actualComponents.Toolbar {...props} />);
    Toolbar.Button = jest.fn((props) => <actualComponents.Toolbar.Button {...props} />);
    Toolbar.Controls = jest.fn((props) => <actualComponents.Toolbar.Controls {...props} />);
    Toolbar.Items = jest.fn((props) => <actualComponents.Toolbar.Items {...props} />);
    Toolbar.Popover = jest.fn((props) => <actualComponents.Toolbar.Popover {...props} />);
    Toolbar.Select = jest.fn((props) => <actualComponents.Toolbar.Select {...props} />);

    return {
        ...actualComponents,
        DatePicker: jest.fn((props) => <actualComponents.DatePicker {...props} />),
        Toolbar,
    };
});

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
    grantedWebspaces: [{key: 'sulu_io', name: 'Sulu IO'}, {key: 'example', name: 'Example'}],
    getWebspace: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function(history) {
    this.history = history;
    this.attributes = {};
    this.route = {options: {}};
}));

const PreviewStoreMock = (PreviewStore: any);

const getPreviewStore = () => {
    const previewStores = PreviewStoreMock.mock.instances;
    if (previewStores.length === 0) {
        throw new Error('Expected PreviewStore to be instantiated');
    }

    return previewStores[previewStores.length - 1];
};

const componentsMock = jest.requireMock('sulu-admin-bundle/components');
const DatePickerMock = (componentsMock.DatePicker: any);
const ToolbarMock = (componentsMock.Toolbar: any);

const getMockCallProps = (mockComponent) => mockComponent.mock.calls.map(([props]) => props);

const getLastMockCallProps = (mockComponent) => {
    const props = getMockCallProps(mockComponent);
    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getLastMockCallPropsMatching = (mockComponent, matcher) => {
    const props = getMockCallProps(mockComponent).filter(matcher);
    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getToolbarSelectProps = (icon) => getLastMockCallPropsMatching(ToolbarMock.Select, (props) => {
    return props.icon === icon;
});

const getToolbarPopoverProps = (icon) => getLastMockCallPropsMatching(ToolbarMock.Popover, (props) => {
    return props.icon === icon;
});

const getDatePickerProps = () => getLastMockCallProps(DatePickerMock);

const renderPreview = (formStore, router) => {
    return render(<Preview formStore={formStore} router={router} />);
};

const startPreview = async(startPromise: Promise<mixed>) => {
    await act(async() => {
        screen.getByRole('button', {name: 'Start'}).click();
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
            {key: 's', title: 'Summer', default: false},
            {key: 'w', title: 'Winter', default: true},
        ],
    });

    const {asFragment} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    expect(previewStore.resourceKey).toBe('pages');
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

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
            {key: 's', title: 'Summer', default: false},
            {key: 'w', title: 'Winter', default: true},
        ],
    });

    renderPreview(formStore, router);

    const previewStore = getPreviewStore();
    expect(previewStore.resourceKey).toBe('page_contents');
});

test('Render correct preview with target groups', async() => {
    const targetGroupsPromise = Promise.resolve({_embedded: {target_groups: []}});
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    Preview.audienceTargeting = true;
    const {asFragment} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await act(async() => {
        await targetGroupsPromise;
    });

    await startPreview(startPromise);

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

    const {asFragment, container} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

    expect(asFragment()).toMatchSnapshot();

    act(() => {
        screen.getByRole('button', {name: /sulu_preview.open_in_window/}).click();
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

    const {container} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

    expect(container.querySelectorAll('.auto')).toHaveLength(1);
    expect(container.querySelectorAll('.desktop')).toHaveLength(0);
    expect(container.querySelectorAll('.tablet')).toHaveLength(0);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

    act(() => {
        getToolbarSelectProps('su-expand').onChange('tablet');
    });

    expect(container.querySelectorAll('.auto')).toHaveLength(0);
    expect(container.querySelectorAll('.desktop')).toHaveLength(0);
    expect(container.querySelectorAll('.tablet')).toHaveLength(1);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

    act(() => {
        getToolbarSelectProps('su-expand').onChange('desktop');
    });

    expect(container.querySelectorAll('.auto')).toHaveLength(0);
    expect(container.querySelectorAll('.desktop')).toHaveLength(1);
    expect(container.querySelectorAll('.tablet')).toHaveLength(0);
    expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

    act(() => {
        getToolbarSelectProps('su-expand').onChange('smartphone');
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

    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'sulu_io', undefined);

    act(() => {
        getToolbarSelectProps('su-webspace').onChange('example');
    });

    expect(previewStore.setWebspace).toBeCalledWith('example');
});

test('Use router attribute to determine webspace', async() => {
    const locale = observable.box('ru');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});
    router.attributes.webspace = 'example';

    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'example', undefined);
});

test('Change segment in PreviewStore when selection of segment has changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', title: 'Summer', default: false},
            {key: 'w', title: 'Winter', default: true},
        ],
    });

    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, undefined, 'sulu_io', 'w');

    act(() => {
        getToolbarSelectProps('su-focus').onChange('s');
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
    const {asFragment} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    await startPreview(startPromise);

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
    const {asFragment} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    await startPreview(startPromise);

    act(() => {
        screen.getByRole('button', {name: /sulu_preview.open_in_window/}).click();
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
    const {asFragment} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    await startPreview(startPromise);

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
    const {asFragment} = renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = true;

    await startPreview(startPromise);

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
    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

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
    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

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

    const targetGroupsPromise = Promise.resolve({
        _embedded: {
            target_groups: [
                {id: 4, title: 'Target Group'},
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    Preview.audienceTargeting = true;

    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    await act(async() => {
        await targetGroupsPromise;
    });

    await startPreview(startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, locale, 'sulu_io', undefined);

    act(() => {
        getToolbarSelectProps('su-user').onChange(4);
    });

    expect(previewStore.setTargetGroup).toBeCalledWith(4);
    expect(previewStore.update).toBeCalledWith(undefined);
});

test('Change dateTime in PreviewStore when DatePicker changed', async() => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = new Router({});

    renderPreview(formStore, router);

    const startPromise = Promise.resolve();
    const previewStore = getPreviewStore();
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    await startPreview(startPromise);

    expect(PreviewStore).toBeCalledWith('pages', undefined, undefined, 'sulu_io', undefined);

    render(<>{getToolbarPopoverProps('su-calendar').children()}</>);

    const date = new Date();
    act(() => {
        getDatePickerProps().onChange(date);
    });

    expect(previewStore.setDateTime).toBeCalledWith(date);
});
