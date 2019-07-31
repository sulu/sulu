// @flow
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import {ResourceFormStore} from '../../../../containers/Form';
import {ResourceRequester} from '../../../../services';
import PublishTogglerToolbarAction from '../../toolbarActions/PublishTogglerToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));

jest.mock('../../../../containers/Form', () => ({
    ResourceFormStore: class {
        resourceStore;

        resourceKey = 'test';

        set = jest.fn();

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
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

function createPublishTogglerToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new PublishTogglerToolbarAction(resourceFormStore, form, router);
}

test('Return item config with correct type, label, loading and value', () => {
    const toolbarAction = createPublishTogglerToolbarAction();
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

test('Return correct value when resource is already published', () => {
    const toolbarAction = createPublishTogglerToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = true;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        value: true,
    }));
});

test('Return null as item config when resource store is loading', () => {
    const toolbarAction = createPublishTogglerToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Return null as item config when resource has no id yet', () => {
    const toolbarAction = createPublishTogglerToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = null;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Call ResourceRequester with correct parameters when resource is not published and toggler is clicked', () => {
    const publishPromise = Promise.resolve({published: true});
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createPublishTogglerToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = false;
    // $FlowFixMe
    toolbarAction.resourceFormStore.resourceStore.locale = 'de';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    expect(ResourceRequester.post).toBeCalledWith(
        'test',
        undefined,
        {action: 'publish', id: 1234, locale: 'de'}
    );
});

test('Call ResourceRequester with correct parameters when resource is already published and toggler is clicked', () => {
    const publishPromise = Promise.resolve({published: false});
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createPublishTogglerToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = true;
    // $FlowFixMe
    toolbarAction.resourceFormStore.resourceStore.locale = 'de';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    expect(ResourceRequester.post).toBeCalledWith(
        'test',
        undefined,
        {action: 'unpublish', id: 1234, locale: 'de'}
    );
});

test('Return item config with loading toggler during request', () => {
    const publishPromise = Promise.resolve({published: true});
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createPublishTogglerToolbarAction();
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

test('Set new published value to ResourceFormStore and show success-snackbar on successful request', () => {
    const publishPromise = Promise.resolve({published: true});
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createPublishTogglerToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.published = false;

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    return publishPromise.then(() => {
        expect(toolbarAction.resourceFormStore.set).toBeCalledWith('published', true);
        expect(toolbarAction.form.showSuccessSnackbar).toBeCalled();
    });
});

test('Push error to form view on failed request', (done) => {
    const publishPromise = Promise.reject();
    ResourceRequester.post.mockReturnValue(publishPromise);

    const toolbarAction = createPublishTogglerToolbarAction();
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
