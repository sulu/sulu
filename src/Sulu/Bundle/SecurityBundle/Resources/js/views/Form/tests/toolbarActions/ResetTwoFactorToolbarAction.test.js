// @flow
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Router from 'sulu-admin-bundle/services/Router';
import Form from 'sulu-admin-bundle/views/Form/Form';
import {ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import ResetTwoFactorToolbarAction from '../../toolbarActions/ResetTwoFactorToolbarAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;

        change = jest.fn();

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
    }
));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

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

function createResetTwoFactorToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new ResetTwoFactorToolbarAction(resourceFormStore, form, router, [], {}, resourceStore);
}

test('Return item config with correct type, icon and label', () => {
    const toolbarAction = createResetTwoFactorToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.twoFactor = {method: 'totp'};

    expect(toolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        type: 'button',
        icon: 'su-lock',
        label: 'sulu_security.reset_two_factor',
        loading: false,
    }));
});

test('Return null as item config when resource store is loading', () => {
    const toolbarAction = createResetTwoFactorToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = true;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.twoFactor = {method: 'totp'};

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Return null as item config when user has no id yet', () => {
    const toolbarAction = createResetTwoFactorToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = null;
    toolbarAction.resourceFormStore.resourceStore.data.twoFactor = {method: 'totp'};

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Return null as item config when user has no two factor method', () => {
    const toolbarAction = createResetTwoFactorToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.twoFactor = undefined;

    expect(toolbarAction.getToolbarItemConfig()).toBeFalsy();
});

test('Call ResourceRequester with correct parameters when button is clicked', () => {
    const resetTwoFactorPromise = Promise.resolve({});
    ResourceRequester.post.mockReturnValue(resetTwoFactorPromise);

    const toolbarAction = createResetTwoFactorToolbarAction();
    toolbarAction.resourceFormStore.resourceStore.loading = false;
    toolbarAction.resourceFormStore.resourceStore.data.id = 1234;
    toolbarAction.resourceFormStore.resourceStore.data.twoFactor = {method: 'totp'};
    // $FlowFixMe
    toolbarAction.resourceFormStore.resourceStore.locale = 'de';

    const toolbarItemConfig = toolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The ToolbarItemConfig should not be undefined or null');
    }
    toolbarItemConfig.onClick();

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'users',
        undefined,
        {action: 'reset-two-factor', id: 1234, locale: 'de'}
    );

    return resetTwoFactorPromise.then(() => {
        expect(toolbarAction.resourceFormStore.change)
            .toHaveBeenCalledWith('twoFactor', undefined, {isServerValue: true});
    });
});
