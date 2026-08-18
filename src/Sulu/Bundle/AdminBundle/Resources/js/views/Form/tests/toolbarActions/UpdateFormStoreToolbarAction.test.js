// @flow
import {mount} from 'enzyme';
import symfonyRouting from 'fos-jsrouting/router';
import UpdateFormStoreToolbarAction from '../../toolbarActions/UpdateFormStoreToolbarAction';
import {setAccountLimitContactEmail} from '../../../../containers/AiApplication/accountLimits';
import {ResourceFormStore} from '../../../../containers/Form';
import memoryFormStoreFactory from '../../../../containers/Form/stores/memoryFormStoreFactory';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import Requester from '../../../../services/Requester';

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

jest.mock('fos-jsrouting/router', () => ({
    generate: jest.fn(),
}));

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../../containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        data: {},
        validate: jest.fn().mockReturnValue(true),
        destroy: jest.fn(),
    })),
}));

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        options = {};

        setMultiple = jest.fn();

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
    }
));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.route = {
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
    this.showSuccessSnackbar = jest.fn();
    this.errors = require('mobx').observable([]);
    this.warnings = require('mobx').observable([]);
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.data = {};
    this.observableOptions = observableOptions;
    this.locale = {
        get: jest.fn(),
    };
}));

function createUpdateFormStoreToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });
    return new UpdateFormStoreToolbarAction(
        resourceFormStore,
        form,
        router,
        [],
        {
            icon: 'su-pen',
            route: 'test_route',
            contentExpressions: [{property: 'test', get: 'test', path: '/test'}],
            dialogKey: 'test_dialog',
            dialogTitle: 'Test Dialog',
            dialogDescription: 'Test Description',
            dialogCancelText: 'Cancel',
            dialogOkText: 'OK',
            label: 'Test Dialog',
            ...options,
        },
        resourceStore
    );
}

test('Throw error if required options are missing', () => {
    expect(() => createUpdateFormStoreToolbarAction({icon: undefined}))
        .toThrow(/Missing required options/);
});

test('Throw error if contentExpressions is not an array', () => {
    expect(() => createUpdateFormStoreToolbarAction({contentExpressions: {}}))
        .toThrow(/contentExpressions must be an array/);
});

test('Return correct toolbar item config', () => {
    const action = createUpdateFormStoreToolbarAction({label: 'Update'});
    const config = action.getToolbarItemConfig();

    expect(config).toEqual({
        type: 'button',
        label: 'Update',
        icon: 'su-pen',
        onClick: expect.any(Function),
        loading: false,
    });
});

test('Open dialog on button click when content exists', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.data = {test: 'test content'};

    const config = action.getToolbarItemConfig();
    await config.onClick();

    expect(action.showDialog).toBe(true);
});

test('Pass form metadata options to dialog form store', async() => {
    const action = createUpdateFormStoreToolbarAction({
        formKey: 'test_form',
        formMetadataOptionsExpressions: [
            {
                property: 'excludedLocale',
                get: '_locale',
            },
        ],
    });
    action.resourceFormStore.resourceStore.data = {test: 'test content'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');

    const config = action.getToolbarItemConfig();
    await config.onClick();

    expect(memoryFormStoreFactory.createFromFormKey).toHaveBeenCalledWith(
        'test_form',
        undefined,
        undefined,
        undefined,
        {excludedLocale: 'en'}
    );
});

test('Fetch data directly when no content exists', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.resourceFormStore.resourceStore.data = {};
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({});

    const config = action.getToolbarItemConfig();
    await config.onClick();

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.showDialog).toBe(false);
    expect(Requester.post).toHaveBeenCalled();
});

test('Close dialog on cancel', () => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;

    const element = mount(action.getNode());
    element.find('Button[skin="secondary"]').simulate('click');

    expect(action.showDialog).toBe(false);
});

test('Fetch data on confirm', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {
        test: 'test content',
    };
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en');
    Requester.post.mockResolvedValue({});

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    expect(action.loading).toBe(true);

    await new Promise((resolve) => setTimeout(resolve));

    expect(Requester.post).toHaveBeenCalledWith('/test/5?locale=en', {
        content: {test: 'test content'},
        data: {},
    });
    expect(action.loading).toBe(false);
    expect(action.showDialog).toBe(false);
});

test('Passes webspaceKey from options to route generation', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;
    action.resourceFormStore.resourceStore.id = 5;
    action.resourceFormStore.resourceStore.data = {id: 5};
    action.resourceFormStore.options = {webspace: 'sulu_io'};
    // $FlowFixMe
    action.resourceFormStore.locale.get = jest.fn().mockReturnValue('en');
    // $FlowFixMe
    action.resourceFormStore.change = jest.fn();

    symfonyRouting.generate.mockReturnValue('/test/5?locale=en&webspaceKey=sulu_io');
    Requester.post.mockResolvedValue({});

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(symfonyRouting.generate).toHaveBeenCalledWith('test_route', expect.objectContaining({
        id: 5,
        locale: 'en',
        webspaceKey: 'sulu_io',
    }));
});

test('Handle error on fetch', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'error.message'});
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.loading).toBe(false);
    expect(action.showDialog).toBe(false);
    expect(action.form.errors).toContain('error.message');
});

test('Turn a temporary messageKey into a warning whose action retries the request', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.ai_request_failed'});
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.form.errors).toHaveLength(0);
    expect(action.form.warnings).toHaveLength(1);
    const warning = action.form.warnings[0];
    // $FlowFixMe
    expect(warning.title).toEqual('sulu_admin.request_failed');
    // $FlowFixMe
    expect(warning.actions[0].label).toEqual('sulu_admin.try_again');

    // $FlowFixMe
    warning.actions[0].onClick();
    await new Promise((resolve) => setTimeout(resolve));

    expect(action.form.warnings).toHaveLength(1);
    expect(action.form.warnings[0]).not.toBe(warning);
    expect(Requester.post).toHaveBeenCalledTimes(2);
});

test('Turn an account limit messageKey into a titled error with a contact action', async() => {
    setAccountLimitContactEmail('admin@example.com');

    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockResolvedValue({messageKey: 'sulu_ai.out_of_credits'});
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    const lastError = action.form.errors[action.form.errors.length - 1];
    expect(lastError).toEqual(expect.objectContaining({
        title: 'sulu_ai.out_of_credits',
        message: 'sulu_ai.out_of_credits_description',
    }));
    // $FlowFixMe
    expect(lastError.actions).toHaveLength(1);
    // $FlowFixMe
    expect(lastError.actions[0].label).toEqual('sulu_admin.contact_admin');

    setAccountLimitContactEmail(undefined);
});

test('Handle plain object error on fetch', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;
    Requester.post.mockRejectedValue({messageKey: 'plain.error'});

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.loading).toBe(false);
    expect(action.showDialog).toBe(false);
    expect(action.form.errors).toContain('plain.error');
});

test('Handle invalid json error on fetch', async() => {
    const action = createUpdateFormStoreToolbarAction();
    action.showDialog = true;

    const error = new Error('Test Error');
    // $FlowFixMe
    error.json = jest.fn().mockRejectedValue(new Error('invalid json'));
    Requester.post.mockRejectedValue(error);

    const element = mount(action.getNode());
    element.find('Button[skin="primary"]').simulate('click');

    await new Promise((resolve) => setTimeout(resolve));

    expect(action.loading).toBe(false);
    expect(action.showDialog).toBe(false);
    expect(action.form.errors).toContain('sulu_admin.error');
});

test('Render dialog with correct props', () => {
    const action = createUpdateFormStoreToolbarAction({
        dialogCancelText: 'Cancel Test',
        dialogOkText: 'OK Test',
    });
    action.showDialog = true;

    const element = mount(action.getNode());
    const dialog = element.find('Dialog');

    expect(dialog.prop('cancelText')).toBe('Cancel Test');
    expect(dialog.prop('confirmText')).toBe('OK Test');
    expect(dialog.prop('title')).toBe('Test Dialog');
    expect(dialog.prop('children')).toContain('Test Description');
});
