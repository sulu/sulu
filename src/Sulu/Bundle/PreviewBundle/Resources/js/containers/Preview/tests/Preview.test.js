// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import {
    createRoute,
    createRouterMock,
    mockResizeObserver,
} from 'sulu-admin-bundle/utils/TestHelper';
import {webspaceStore} from 'sulu-page-bundle/stores';
import PreviewStore from '../stores/PreviewStore';
import Preview from '../Preview';

let mockPreviewStoreInstances = [];

window.open = jest.fn().mockReturnValue({addEventListener: jest.fn()});

mockResizeObserver();

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

jest.mock('debounce', () => jest.fn((value) => {
    value.clear = jest.fn();

    return value;
}));

jest.mock('../stores/PreviewStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.starting = false;
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
    mockPreviewStoreInstances.push(this);
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
    grantedWebspaces: [{key: 'sulu_io', name: 'Sulu IO'}, {key: 'example', name: 'Example'}],
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
    jest.clearAllMocks();

    Preview.mode = 'on_request';
    Preview.audienceTargeting = false;

    webspaceStore.getWebspace.mockReturnValue({segments: []});
    ResourceRequester.getList.mockReturnValue(Promise.resolve({_embedded: {target_groups: []}}));
    window.open.mockReturnValue({addEventListener: jest.fn()});
    mockPreviewStoreInstances = [];
});

function createPreviewRouter(routeOptions: Object = {}, attributes: Object = {}) {
    return createRouterMock({
        attributes,
        route: createRoute(routeOptions),
    });
}

function getPreviewStore() {
    const previewStore = mockPreviewStoreInstances[mockPreviewStoreInstances.length - 1];

    if (!previewStore) {
        throw new Error('Expected a preview store to be created');
    }

    return previewStore;
}

async function startPreview(user) {
    await user.click(screen.getByRole('button', {name: 'Start'}));

    return getPreviewStore();
}

async function selectToolbarOption(user, icon: string, option: string) {
    await user.click(screen.getByRole('button', {name: new RegExp(icon)}));
    await user.click(screen.getByRole('button', {name: option}));
}

test('Render correct preview', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', default: false},
            {key: 'w', name: 'Winter', default: true},
        ],
    });

    const {container} = render(<Preview formStore={formStore} router={router} />);
    const previewStore = getPreviewStore();
    expect(previewStore.resourceKey).toBe('pages');
    await startPreview(user);
    expect(await screen.findByRole('button', {name: /su-focus/})).toBeInTheDocument();
    expect(container).toMatchSnapshot();
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

    render(<Preview formStore={formStore} router={router} />);

    const previewStore = getPreviewStore();
    expect(previewStore.resourceKey).toBe('page_contents');
});

test('Render correct preview with target groups', async() => {
    const user = userEvent.setup();
    const targetGroupsPromise = Promise.resolve({_embedded: {target_groups: []}});
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    Preview.audienceTargeting = true;
    const {container} = render(<Preview formStore={formStore} router={router} />);

    await targetGroupsPromise;
    await startPreview(user);
    expect(await screen.findByRole('button', {name: /su-user/})).toBeInTheDocument();
    expect(container).toMatchSnapshot();
});

test('Render button to start preview', () => {
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {container} = render(<Preview formStore={formStore} router={router} />);

    expect(container).toMatchSnapshot();
});

test('Render nothing if separate window is opened and rerender if it is closed', async() => {
    const user = userEvent.setup();
    const previewWindow = {addEventListener: jest.fn()};
    window.open.mockReturnValue(previewWindow);

    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    const {container} = render(<Preview formStore={formStore} router={router} />);

    await startPreview(user);
    expect(container).toMatchSnapshot();
    await user.click(screen.getByRole('button', {name: /su-link/}));
    expect(container).toBeEmptyDOMElement();

    expect(previewWindow.addEventListener).toHaveBeenCalledWith('beforeunload', expect.anything());
    act(() => {
        previewWindow.addEventListener.mock.calls[0][1]();
    });
    expect(container).toMatchSnapshot();
});

test('Change css class when selection of device has changed', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    render(<Preview formStore={formStore} router={router} />);

    await startPreview(user);
    expect(document.querySelectorAll('.auto')).toHaveLength(1);

    await selectToolbarOption(user, 'su-expand', 'sulu_preview.tablet');
    expect(document.querySelectorAll('.tablet')).toHaveLength(1);

    await selectToolbarOption(user, 'su-expand', 'sulu_preview.desktop');
    expect(document.querySelectorAll('.desktop')).toHaveLength(1);

    await selectToolbarOption(user, 'su-expand', 'sulu_preview.smartphone');
    expect(document.querySelectorAll('.smartphone')).toHaveLength(1);
});

test('Change webspace in PreviewStore when selection of webspace has changed', async() => {
    const user = userEvent.setup();
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    render(<Preview formStore={formStore} router={router} />);
    const previewStore = getPreviewStore();

    await startPreview(user);
    expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'sulu_io', undefined);

    await selectToolbarOption(user, 'su-webspace', 'Example');
    expect(previewStore.setWebspace).toHaveBeenCalledWith('example');
});

test('Use router attribute to determine webspace', () => {
    const locale = observable.box('ru');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter({}, {webspace: 'example'});

    render(<Preview formStore={formStore} router={router} />);

    expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'example', undefined);
});

test('Change segment in PreviewStore when selection of segment has changed', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    webspaceStore.getWebspace.mockReturnValue({
        segments: [
            {key: 's', name: 'Summer', title: 'Summer', default: false},
            {key: 'w', name: 'Winter', title: 'Winter', default: true},
        ],
    });

    render(<Preview formStore={formStore} router={router} />);
    const previewStore = getPreviewStore();

    await startPreview(user);
    expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, undefined, 'sulu_io', 'w');

    await selectToolbarOption(user, 'su-focus', 'Summer');
    expect(previewStore.setSegment).toHaveBeenCalledWith('s');
});

test('React and update preview when data is changed', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = createPreviewRouter();
    const {container} = render(<Preview formStore={formStore} router={router} />);
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.token = '123-123-123';

    await startPreview(user);
    act(() => {
        formStore.data.set('title', 'New Test');
    });
    await waitFor(() => expect(previewStore.update).toHaveBeenCalledWith({title: 'New Test'}));
    expect(container).toMatchSnapshot();
});

test('Does not push an update on start when the form data has not changed', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = createPreviewRouter();
    render(<Preview formStore={formStore} router={router} />);

    const previewStore = getPreviewStore();
    previewStore.update.mockReturnValue(Promise.resolve('<h1>Sulu is awesome</h1>'));
    previewStore.token = '123-123-123';

    await startPreview(user);

    // An unchanged first render must not trigger a redundant preview update.
    expect(previewStore.update).not.toHaveBeenCalled();
});

test('React and update preview in external window when data is changed', async() => {
    const user = userEvent.setup();
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
    const {container} = render(<Preview formStore={formStore} router={router} />);
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.token = '123-123-123';

    await startPreview(user);
    await user.click(screen.getByRole('button', {name: /su-link/}));
    act(() => {
        formStore.data.set('title', 'New Test');
    });
    await waitFor(() => expect(previewStore.update).toHaveBeenCalledWith({title: 'New Test'}));

    expect(container).toMatchSnapshot();
    expect(previewWindow.document.open).toHaveBeenCalledWith();
    expect(previewWindow.document.write).toHaveBeenCalledWith('<h1>Sulu is awesome</h1>');
    expect(previewWindow.document.close).toHaveBeenCalledWith();
});

test('Dont react or update preview when data is changed during formstore is loading', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = true;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = createPreviewRouter();
    const {container} = render(<Preview formStore={formStore} router={router} />);
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.token = '123-123-123';

    await startPreview(user);
    act(() => {
        formStore.data.set('title', 'New Test');
    });
    expect(previewStore.update).not.toHaveBeenCalled();
    expect(container).toMatchSnapshot();
});

test('Dont react or update preview when data is changed during preview-store is starting', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');

    // $FlowFixMe
    formStore.data = observable.map({title: 'Test'});
    // $FlowFixMe
    formStore.loading = false;
    // $FlowFixMe
    formStore.type = observable.box('default');

    const router = createPreviewRouter();
    const {container} = render(<Preview formStore={formStore} router={router} />);
    const updatePromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.update.mockReturnValue(updatePromise);
    previewStore.starting = true;
    previewStore.token = '123-123-123';

    await startPreview(user);
    act(() => {
        formStore.data.set('title', 'New Test');
    });
    expect(previewStore.update).not.toHaveBeenCalled();
    expect(container).toMatchSnapshot();
});

test('React and update-context when schema is changed', async() => {
    const user = userEvent.setup();
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
    render(<Preview formStore={formStore} router={router} />);
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.updateContext.mockReturnValue(updateContextPromise);

    await startPreview(user);

    act(() => {
        (formStore.type: any).set('homepage');
        (formStore.schema: any).set({title: {label: 'Title', colSpan: 12}});
    });

    await waitFor(() => expect(previewStore.updateContext).toHaveBeenCalledWith('homepage', {title: 'Test'}));
});

test('React and restart when locale is changed', async() => {
    const user = userEvent.setup();
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
    render(<Preview formStore={formStore} router={router} />);
    const updateContextPromise = Promise.resolve('<h1>Sulu is awesome</h1>');

    const previewStore = getPreviewStore();
    previewStore.updateContext.mockReturnValue(updateContextPromise);

    await startPreview(user);

    act(() => {
        (formStore.type: any).set('homepage');
        (formStore.locale: any).set('de');
    });

    await waitFor(() => expect(previewStore.restart).toHaveBeenCalled());
});

test('Change target group in PreviewStore when selection of target group has changed', async() => {
    const user = userEvent.setup();
    const locale = observable.box('de');
    const resourceStore = new ResourceStore('pages', 1, {locale});
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    Preview.audienceTargeting = true;
    const targetGroupsPromise = Promise.resolve({
        _embedded: {target_groups: [{id: 4, title: 'Target Group'}]},
    });
    ResourceRequester.getList.mockReturnValue(targetGroupsPromise);

    render(<Preview formStore={formStore} router={router} />);
    const previewStore = getPreviewStore();
    previewStore.update.mockReturnValue(Promise.resolve('<h1>Sulu is awesome</h1>'));
    previewStore.token = '123-123-123';

    await targetGroupsPromise;
    expect(await screen.findByRole('button', {name: 'Start'})).toBeInTheDocument();
    await startPreview(user);
    expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, locale, 'sulu_io', undefined);

    await selectToolbarOption(user, 'su-user', 'Target Group');
    expect(previewStore.setTargetGroup).toHaveBeenCalledWith(4);
    expect(previewStore.update).toHaveBeenCalledWith({});
});

test('Change dateTime in PreviewStore when DatePicker changed', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    const router = createPreviewRouter();

    render(<Preview formStore={formStore} router={router} />);
    const previewStore = getPreviewStore();

    await startPreview(user);
    expect(PreviewStore).toHaveBeenCalledWith('pages', undefined, undefined, 'sulu_io', undefined);

    await user.click(screen.getByRole('button', {name: /su-calendar/}));
    const input = screen.getByRole('textbox');
    await user.clear(input);
    await user.paste('12/16/2020 2:06 PM');
    await user.tab();

    expect(previewStore.setDateTime).toHaveBeenCalled();
    const selectedDate = previewStore.setDateTime.mock.calls[previewStore.setDateTime.mock.calls.length - 1][0];
    expect(selectedDate.getTime()).not.toBeNaN();
});

test('Use mainWebspace from formStore data as default webspace', () => {
    const resourceStore = new ResourceStore('articles', 1);
    const formStore = new ResourceFormStore(resourceStore, 'articles');

    // $FlowFixMe
    formStore.data = {mainWebspace: 'example'};

    render(<Preview formStore={formStore} router={createPreviewRouter()} />);

    expect(PreviewStore).toHaveBeenCalledWith('articles', undefined, undefined, 'example', undefined);
});

test('Fall back to first webspace when mainWebspace is not in webspace options', () => {
    const resourceStore = new ResourceStore('articles', 1);
    const formStore = new ResourceFormStore(resourceStore, 'articles');

    // $FlowFixMe
    formStore.data = {mainWebspace: 'non_existent_webspace'};

    render(<Preview formStore={formStore} router={createPreviewRouter()} />);

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

    try {
        render(<Preview formStore={formStore} router={createPreviewRouter()} />);

        expect(PreviewStore).toHaveBeenCalledWith('articles', undefined, locale, 'example', undefined);
    } finally {
        // $FlowFixMe
        webspaceStore.grantedWebspaces = grantedWebspaces;
    }
});

// A class instance like a real window, which mobx keeps by reference instead of copying it into an observable
class PreviewWindowMock {
    addEventListener = jest.fn();
}

async function renderWithPreviewWindow(user, formStore) {
    const previewWindow = new PreviewWindowMock();
    window.open.mockReturnValue(previewWindow);

    render(<Preview formStore={formStore} router={createPreviewRouter()} />);

    await startPreview(user);
    await user.click(screen.getByRole('button', {name: /su-link/}));

    return previewWindow;
}

function postPreviewMessage(source: Object, data: Object, origin: string = window.location.origin) {
    const event = new MessageEvent('message', {data, origin});
    Object.defineProperty(event, 'source', {value: source});

    act(() => {
        window.dispatchEvent(event);
    });
}

function appendBlockElement(id: string, parent: ?HTMLElement = document.body): HTMLElement {
    const element = document.createElement('section');
    element.setAttribute('data-sulu-block-id', id);
    // $FlowFixMe
    element.scrollIntoView = jest.fn();
    // $FlowFixMe
    element.animate = jest.fn();

    if (parent) {
        parent.appendChild(element);
    }

    return element;
}

test('Scroll to and expand a block referenced by a preview navigate click, mounting nested ' +
    'content on demand like a real collapsed block would', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'parent-id', items: [{_id: 'child-id'}]}]};

    const previewWindow = await renderWithPreviewWindow(user, formStore);

    const parent = appendBlockElement('parent-id');
    // A collapsed block hides its nested fields, so "child" only mounts once "parent" is clicked.
    const child = appendBlockElement('child-id', null);
    const handleParentClick = jest.fn(() => parent.appendChild(child));
    const handleChildClick = jest.fn();
    parent.addEventListener('click', handleParentClick);
    child.addEventListener('click', handleChildClick);

    postPreviewMessage(previewWindow, {type: 'sulu.preview.navigate', id: 'child-id'});

    expect(parent.contains(child)).toBe(true);
    expect(handleParentClick).toHaveBeenCalled();
    // The target may be collapsed too, so it is clicked/expanded before scrolling.
    expect(handleChildClick).toHaveBeenCalled();
    expect(child.scrollIntoView).toHaveBeenCalledWith({behavior: 'smooth', block: 'start'});
    expect(child.animate).toHaveBeenCalledWith(expect.any(Array), expect.objectContaining({duration: 2500}));
    expect(parent.animate).not.toHaveBeenCalled();

    parent.remove();
});

test('Expands the target block itself (not just its ancestors) for a top-level, non-nested block', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'block-1'}]};

    const previewWindow = await renderWithPreviewWindow(user, formStore);

    const block = appendBlockElement('block-1');
    const handleClick = jest.fn();
    block.addEventListener('click', handleClick);

    postPreviewMessage(previewWindow, {type: 'sulu.preview.navigate', id: 'block-1'});

    expect(handleClick).toHaveBeenCalled();
    expect(block.scrollIntoView).toHaveBeenCalledWith({behavior: 'smooth', block: 'start'});

    block.remove();
});

test('Scrolls to the block without a highlight when the browser cannot animate elements', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'block-1'}]};

    const previewWindow = await renderWithPreviewWindow(user, formStore);

    const block = appendBlockElement('block-1');
    // $FlowFixMe
    block.animate = undefined;

    postPreviewMessage(previewWindow, {type: 'sulu.preview.navigate', id: 'block-1'});

    expect(block.scrollIntoView).toHaveBeenCalledWith({behavior: 'smooth', block: 'start'});

    block.remove();
});

test('Does nothing when the referenced block is not present in the form data', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'block-1'}]};

    const previewWindow = await renderWithPreviewWindow(user, formStore);

    const block = appendBlockElement('block-1');
    const handleClick = jest.fn();
    block.addEventListener('click', handleClick);

    postPreviewMessage(previewWindow, {type: 'sulu.preview.navigate', id: 'unknown-id'});

    expect(handleClick).not.toHaveBeenCalled();
    expect(block.scrollIntoView).not.toHaveBeenCalled();

    block.remove();
});

test('Gives up instead of polling forever when an ancestor never mounts its nested content', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'parent-id', items: [{_id: 'child-id'}]}]};

    const previewWindow = await renderWithPreviewWindow(user, formStore);

    // "child" is intentionally never appended, simulating a block that never mounts.
    const parent = appendBlockElement('parent-id');
    const handleParentClick = jest.fn();
    parent.addEventListener('click', handleParentClick);

    postPreviewMessage(previewWindow, {type: 'sulu.preview.navigate', id: 'child-id'});

    expect(handleParentClick).toHaveBeenCalledTimes(1);

    parent.remove();
});

test('Ignores messages that do not originate from its own preview window', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'block-1'}]};

    await renderWithPreviewWindow(user, formStore);

    const block = appendBlockElement('block-1');
    const handleClick = jest.fn();
    block.addEventListener('click', handleClick);

    postPreviewMessage({}, {type: 'sulu.preview.navigate', id: 'block-1'});

    expect(handleClick).not.toHaveBeenCalled();

    block.remove();
});

test('Ignores messages whose source matches but whose origin does not (source survives ' +
    'cross-origin navigation of the preview window)', async() => {
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('pages', 1);
    const formStore = new ResourceFormStore(resourceStore, 'pages');
    // $FlowFixMe
    formStore.data = {blocks: [{_id: 'block-1'}]};

    const previewWindow = await renderWithPreviewWindow(user, formStore);

    const block = appendBlockElement('block-1');
    const handleClick = jest.fn();
    block.addEventListener('click', handleClick);

    postPreviewMessage(previewWindow, {type: 'sulu.preview.navigate', id: 'block-1'}, 'https://attacker.example');

    expect(handleClick).not.toHaveBeenCalled();

    block.remove();
});
