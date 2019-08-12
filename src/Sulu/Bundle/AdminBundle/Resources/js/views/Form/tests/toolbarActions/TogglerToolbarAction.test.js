// @flow
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import {ResourceFormStore} from '../../../../containers/Form';
import {ResourceRequester} from '../../../../services';
import TogglerToolbarAction from '../../toolbarActions/TogglerToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.data = {};
}));

jest.mock('../../../../containers/Form', () => ({
    ResourceFormStore: class {
        resourceStore;
        resourceKey;

        set = jest.fn();

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
            this.resourceKey = resourceStore.resourceKey;
        }

        get id() {
            return this.resourceStore.id;
        }

        get data() {
            return this.resourceStore.data;
        }

        get locale() {
            return this.resourceStore.locale;
        }

        get loading() {
            return this.resourceStore.loading;
        }
    },
}));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.errors = [];
    this.showSuccessSnackbar = jest.fn();
    this.submit = jest.fn();
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    post: jest.fn(),
}));

function createTogglerToolbarAction(resourceKey, options: {[key: string]: mixed}) {
    const resourceStore = new ResourceStore(resourceKey);
    const resourceFormStore = new ResourceFormStore(resourceStore, resourceKey);
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new TogglerToolbarAction(resourceFormStore, form, router, [], options);
}

test('Return item config with correct type, label, loading and value', () => {
    const toolbarAction = createTogglerToolbarAction('test', {label: 'sulu_admin.publish', property: 'published'});
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        type: 'toggler',
        label: 'sulu_admin.publish',
        loading: false,
        value: false,
    }));
});

test.each([
    ['sulu_admin.publish'],
    ['sulu_admin.lock'],
])('Return item config with label "%s"', (label) => {
    const toolbarAction = createTogglerToolbarAction('test', {label, property: 'published'});
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        type: 'toggler',
        label,
        loading: false,
        value: false,
    }));
});

test.each([
    ['published'],
    ['locked'],
])('Return correct value when resource is already in activated state for property "%s"', (property) => {
    const toolbarAction = createTogglerToolbarAction('test', {label: 'Test', property});
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data[property] = true;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        value: true,
    }));
});

test('Return null as item config when resource store is loading', () => {
    const toolbarAction = createTogglerToolbarAction('test', {label: 'Test', property: 'published'});
    toolbarAction.resourceFormStore.resourceStore.loading = true;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Return null as item config when resource has no id yet', () => {
    const toolbarAction = createTogglerToolbarAction('test', {label: 'Test', property: 'published'});
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = null;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test.each([
    ['publish', 'published', 'pages'],
    ['lock', 'locked', 'users'],
])(
    'Call ResourceRequester with correct parameters when resource is not activated and toggler is clicked',
    (action, property, resourceKey) => {
        const promise = Promise.resolve({[property]: true});
        ResourceRequester.post.mockReturnValue(promise);

        const toolbarAction = createTogglerToolbarAction(resourceKey, {label: 'Test', property, activate: action});
        toolbarAction.resourceFormStore.resourceStore.loading = false;
        toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
        toolbarAction.resourceFormStore.resourceStore.data[property] = false;
        // $FlowFixMe
        toolbarAction.resourceFormStore.resourceStore.locale = 'de';

        const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
        if (!toolbarItemConfig) {
            throw new Error('The ToolbarItemConfig should not be undefined or null');
        }
        toolbarItemConfig.onClick();

        expect(ResourceRequester.post).toBeCalledWith(
            resourceKey,
            undefined,
            {action, id: 1234, locale: 'de'}
        );

        return promise.then(() => {
            expect(toolbarAction.resourceFormStore.set).toBeCalledWith(property, true);
            expect(toolbarAction.form.showSuccessSnackbar).toBeCalled();
        });
    }
);

test.each([
    ['unpublish', 'published', 'pages'],
    ['unlock', 'locked', 'users'],
])(
    'Call ResourceRequester with correct parameters when resource is already activated and toggler is clicked',
    (action, property, resourceKey) => {
        const promise = Promise.resolve({[property]: false});
        ResourceRequester.post.mockReturnValue(promise);

        const toolbarAction = createTogglerToolbarAction(resourceKey, {label: 'Test', property, deactivate: action});
        toolbarAction.resourceFormStore.resourceStore.loading = false;
        toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
        toolbarAction.resourceFormStore.resourceStore.data[property] = true;
        // $FlowFixMe
        toolbarAction.resourceFormStore.resourceStore.locale = 'de';

        const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
        if (!toolbarItemConfig) {
            throw new Error('The ToolbarItemConfig should not be undefined or null');
        }
        toolbarItemConfig.onClick();

        expect(ResourceRequester.post).toBeCalledWith(
            resourceKey,
            undefined,
            {action, id: 1234, locale: 'de'}
        );

        return promise.then(() => {
            expect(toolbarAction.resourceFormStore.set).toBeCalledWith(property, false);
            expect(toolbarAction.form.showSuccessSnackbar).toBeCalled();
        });
    }
);

test('Return item config with loading toggler during request', () => {
    const publishPromise = Promise.resolve({published: true});
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createTogglerToolbarAction(
        'test',
        {label: 'Test', property: 'published', activate: 'publish'}
    );
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: false,
    }));

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
    }));

    return publishPromise.then(() => {
        expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
            loading: false,
        }));
    });
});

test('Push error to form view on failed request', (done) => {
    const publishPromise = Promise.reject();
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createTogglerToolbarAction(
        'test',
        {label: 'Test', property: 'published', activate: 'publish'}
    );
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = false;

    expect(toolbarAction.form.errors).toHaveLength(0);

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    setTimeout(() => {
        expect(toolbarAction.form.errors).toHaveLength(1);
        done();
    });
});
