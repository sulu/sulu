// @flow
import React from 'react';
import {observable} from 'mobx';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import {
    createRoute,
    createRouterMock,
    findAllElementsByType,
    findElement,
    mockResizeObserver,
    renderWithRef,
    waitForReaction,
} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import PreviewStore from '../stores/PreviewStore';
import Preview from '../Preview';

window.open = jest.fn().mockReturnValue({addEventListener: jest.fn()});

mockResizeObserver();

// $FlowFixMe
const constantDate = new Date(2020, 11, 16, 14, 6, 22);

// eslint-disable-next-line no-global-assign
Date = class extends Date {
    constructor() {
        return constantDate;
    }
};

jest.mock('debounce', () => jest.fn((value) => {
    value.clear = jest.fn();

    return value;
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

jest.mock('sulu-admin-bundle/utils/Translator');

beforeEach(() => {
    jest.resetModules();

    Preview.mode = 'on_request';
    Preview.audienceTargeting = false;

    webspaceStore.getWebspace.mockReturnValue({segments: []});
});

function createPreviewRouter(routeOptions: Object = {}, attributes: Object = {}) {
    return createRouterMock({
        attributes,
        route: createRoute(routeOptions),
    });
}

function getToolbarSelect(preview, index: number) {
    return findAllElementsByType(preview.render(), 'Select')[index];
}

function getToolbarControlByIcon(preview, icon: string) {
    return findElement(preview.render(), (element) => element.props && element.props.icon === icon);
}

test('Render correct preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    expect(previewStore.resourceKey).toBe('pages');
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        return waitForReaction().then(() => {
            expect(container).toMatchSnapshot();
        });
    });
});

test('Render correct preview use route option for resourceKey', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter({
        previewResourceKey: 'page_contents',
    });

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const previewStore = preview.previewStore;
    expect(previewStore.resourceKey).toBe('page_contents');
});

test('Render correct preview with target groups', () => {
    const targetGroupsPromise = Promise.resolve({_embedded: {target_groups: []}});
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    Preview.audienceTargeting = true;
    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        return waitForReaction().then(() => {
            expect(container).toMatchSnapshot();
        });
    });
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {container} = renderWithRef(<Preview formStore={formStore} router={router} />);

    expect(container).toMatchSnapshot();
});

test('Render nothing if separate window is opened and rerender if it is closed', () => {
    const previewWindow = {addEventListener: jest.fn()};
    window.open.mockReturnValue(previewWindow);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(container).toMatchSnapshot();
        getToolbarControlByIcon(preview, 'su-link').props.onClick();
        expect(container).toBeEmptyDOMElement();

        expect(previewWindow.addEventListener).toHaveBeenCalledWith('beforeunload', expect.anything());
        previewWindow.addEventListener.mock.calls[0][1]();
        expect(container).toMatchSnapshot();
    });
});

test('Change css class when selection of device has changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(container.querySelectorAll('.auto')).toHaveLength(1);
        expect(container.querySelectorAll('.desktop')).toHaveLength(0);
        expect(container.querySelectorAll('.tablet')).toHaveLength(0);
        expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

        getToolbarSelect(preview, 0).props.onChange('tablet');
        expect(container.querySelectorAll('.auto')).toHaveLength(0);
        expect(container.querySelectorAll('.desktop')).toHaveLength(0);
        expect(container.querySelectorAll('.tablet')).toHaveLength(1);
        expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

        getToolbarSelect(preview, 0).props.onChange('desktop');
        expect(container.querySelectorAll('.auto')).toHaveLength(0);
        expect(container.querySelectorAll('.desktop')).toHaveLength(1);
        expect(container.querySelectorAll('.tablet')).toHaveLength(0);
        expect(container.querySelectorAll('.smartphone')).toHaveLength(0);

        getToolbarSelect(preview, 0).props.onChange('smartphone');
        expect(container.querySelectorAll('.auto')).toHaveLength(0);
        expect(container.querySelectorAll('.desktop')).toHaveLength(0);
        expect(container.querySelectorAll('.tablet')).toHaveLength(0);
        expect(container.querySelectorAll('.smartphone')).toHaveLength(1);
    });
});

test('Change webspace in PreviewStore when selection of webspace has changed', () => {
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'sulu_io', undefined);

        getToolbarSelect(preview, 1).props.onChange('example');
        expect(previewStore.setWebspace).toHaveBeenCalledWith('example');
    });
});

test('Use router attribute to determine webspace', () => {
    const locale = observable.box('ru');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter({}, {webspace: 'example'});

    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'example', undefined);
    });
});

test('Change segment in PreviewStore when selection of segment has changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, undefined, 'sulu_io', 'w');

        getToolbarSelect(preview, 2).props.onChange('s');
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

    const router = createPreviewRouter();
    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    preview.handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        return waitForReaction().then(() => {
            previewStore.token = '123-123-123';
            expect(previewStore.update).toHaveBeenCalledWith({title: 'New Test'});

            expect(container).toMatchSnapshot();
        });
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

    const router = createPreviewRouter();
    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    preview.handleStartClick();
    getToolbarControlByIcon(preview, 'su-link').props.onClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        return waitForReaction().then(() => {
            expect(previewStore.update).toHaveBeenCalledWith({title: 'New Test'});

            expect(container).toMatchSnapshot();
            expect(previewWindow.document.open).toHaveBeenCalledWith();
            expect(previewWindow.document.write).toHaveBeenCalledWith('<h1>Sulu is awesome</h1>');
            expect(previewWindow.document.close).toHaveBeenCalledWith();
        });
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

    const router = createPreviewRouter();
    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = false;

    preview.handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        return waitForReaction().then(() => {
            expect(previewStore.update).not.toHaveBeenCalled();

            expect(container).toMatchSnapshot();
        });
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

    const router = createPreviewRouter();
    const {container, instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = true;

    preview.handleStartClick();

    formStore.data.set('title', 'New Test');

    return startPromise.then(() => {
        return waitForReaction().then(() => {
            expect(previewStore.update).not.toHaveBeenCalled();

            expect(container).toMatchSnapshot();
        });
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

    const router = createPreviewRouter();
    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    preview.handleStartClick();

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

    const router = createPreviewRouter();
    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.updateContext.mockReturnValue(updateContextPromise);
    previewStore.starting = false;

    preview.handleStartClick();

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
    const router = createPreviewRouter();

    Preview.audienceTargeting = true;

    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;
    previewStore.token = '123-123-123';

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'sulu_io', undefined);

        getToolbarSelect(preview, 2).props.onChange(4);
        expect(previewStore.setTargetGroup).toHaveBeenCalledWith(4);
        expect(previewStore.update).toHaveBeenCalledWith(undefined);
    });
});

test('Change dateTime in PreviewStore when DatePicker changed', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {instance: preview} = renderWithRef(<Preview formStore={formStore} router={router} />);

    const startPromise = Promise.resolve();
    const previewStore = preview.previewStore;
    previewStore.start.mockReturnValue(startPromise);
    previewStore.starting = false;

    preview.handleStartClick();

    return startPromise.then(() => {
        expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, undefined, 'sulu_io', undefined);

        const date = new Date();
        preview.handleDateTimeChange(date);
        expect(previewStore.setDateTime).toHaveBeenCalledWith(date);
    });
});
