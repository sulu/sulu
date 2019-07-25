// @flow
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Router from 'sulu-admin-bundle/services/Router';
import Form from 'sulu-admin-bundle/views/Form/Form';
import {ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import EnableUserToolbarAction from '../../toolbarActions/EnableUserToolbarAction';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));

jest.mock('sulu-admin-bundle/containers/Form', () => ({
    ResourceFormStore: class {
        resourceStore;

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

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {}));

jest.mock('sulu-admin-bundle/views/Form/Form', () => jest.fn(function() {
    this.errors = [];
    this.showSuccessSnackbar = jest.fn();
    this.submit = jest.fn();
}));

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        post: jest.fn(),
    },
}));

function createEnableUserToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new EnableUserToolbarAction(resourceFormStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and label', () => {
    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        type: 'button',
        icon: 'su-enter',
        label: 'sulu_security.enable_user',
        loading: false,
    }));
});

test('Return null as item config when resource store is loading', () => {
    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Return null as item config when user has no id yet', () => {
    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = null;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Return null as item config when user is already enabled', () => {
    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Call ResourceRequester with correct parameters when button is clicked', () => {
    const deleteDraftPromise = Promise.resolve({enabled: true});
    ResourceRequester.post.mockReturnValue(deleteDraftPromise);

    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;
    // $FlowFixMe
    toolbarAction.resourceFormStore.resourceStore.locale = 'de';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    expect(ResourceRequester.post).toBeCalledWith(
        'users',
        undefined,
        {action: 'enable', id: 1234, locale: 'de'}
    );
});

test('Return item config with loading button during request', () => {
    const deleteDraftPromise = Promise.resolve({enabled: true});
    ResourceRequester.post.mockReturnValue(deleteDraftPromise);

    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;

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

    return deleteDraftPromise.then(() => {
        expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
            loading: false,
        }));
    });
});

test('Set new enabled value to ResourceFormStore and show success-snackbar on successful request', () => {
    const deleteDraftPromise = Promise.resolve({enabled: true});
    ResourceRequester.post.mockReturnValue(deleteDraftPromise);

    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    return deleteDraftPromise.then(() => {
        expect(toolbarAction.resourceFormStore.set).toBeCalledWith('enabled', true);
        expect(toolbarAction.form.showSuccessSnackbar).toBeCalled();
    });
});

test('Push error to form view on failed request', (done) => {
    const deleteDraftPromise = Promise.reject();
    ResourceRequester.post.mockReturnValue(deleteDraftPromise);

    const toolbarAction = createEnableUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;

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
