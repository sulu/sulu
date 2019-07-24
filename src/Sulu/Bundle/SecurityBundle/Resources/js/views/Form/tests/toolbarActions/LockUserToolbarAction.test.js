// @flow
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Router from 'sulu-admin-bundle/services/Router';
import Form from 'sulu-admin-bundle/views/Form/Form';
import {ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import LockUserToolbarAction from '../../toolbarActions/LockUserToolbarAction';

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

function createLockUserToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new LockUserToolbarAction(resourceFormStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and label', () => {
    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        type: 'toggler',
        disabled: false,
        label: 'sulu_security.lock_user',
        loading: false,
        value: false,
    }));
});

test('Return correct label and value when user is already locked', () => {
    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = null;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = true;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        label: 'sulu_security.user_locked',
        value: true,
    }));
});

test('Return item config with disabled button when resource store is loading', () => {
    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return item config with disabled button when user has no id yet', () => {
    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = null;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return null as item config when user is not enabled yet', () => {
    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = false;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Call ResourceRequester with correct parameters when user is not locked and toggler is clicked', () => {
    const lockUserPromise = Promise.resolve({locked: true});
    ResourceRequester.post.mockReturnValue(lockUserPromise);

    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;
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
        {action: 'lock', id: 1234, locale: 'de'}
    );
});

test('Call ResourceRequester with correct parameters when user is already locked and toggler is clicked', () => {
    const unlockUserPromise = Promise.resolve({locked: false});
    ResourceRequester.post.mockReturnValue(unlockUserPromise);

    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = true;
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
        {action: 'unlock', id: 1234, locale: 'de'}
    );
});

test('Return item config with loading button during request', () => {
    const lockUserPromise = Promise.resolve({locked: true});
    ResourceRequester.post.mockReturnValue(lockUserPromise);

    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

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

    return lockUserPromise.then(() => {
        expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
            loading: false,
        }));
    });
});

test('Set new locked value to ResourceFormStore and show success-snackbar on successful request', () => {
    const lockUserPromise = Promise.resolve({locked: true});
    ResourceRequester.post.mockReturnValue(lockUserPromise);

    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    return lockUserPromise.then(() => {
        expect(toolbarAction.resourceFormStore.set).toBeCalledWith('locked', true);
        expect(toolbarAction.form.showSuccessSnackbar).toBeCalled();
    });
});

test('Push error to form view on failed request', (done) => {
    const lockUserPromise = Promise.reject();
    ResourceRequester.post.mockReturnValue(lockUserPromise);

    const toolbarAction = createLockUserToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.enabled = true;
    toolbarAction.resourceFormStore.resourceStore.data.locked = false;

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
