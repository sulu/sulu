// @flow
import log from 'loglevel';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceRequester from '../../../../services/ResourceRequester';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import CopyLocaleToolbarAction from '../../toolbarActions/CopyLocaleToolbarAction';
import metadataStore from '../../../../containers/Form/stores/metadataStore';

const FORM = {
    locales: {
        label: 'Choose destination locales',
        type: 'select',
        options: {
            values: {
                value: [
                    {value: 'de', title: 'de'},
                    {value: 'en', title: 'en'},
                ],
            },
            default_value: {
                value: 'de',
            },
        },
        required: true,
    },
};

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.observableOptions = observableOptions;
    this.data = {};
    this.locale = {
        get: jest.fn(),
    };
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../containers/Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve(FORM)),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
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
}));

function createCopyLocaleToolbarAction(locales, options = {}) {
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new CopyLocaleToolbarAction(formStore, form, router, locales, options, resourceStore);
}

function getDialogProps(copyLocaleToolbarAction: CopyLocaleToolbarAction): any {
    return ((copyLocaleToolbarAction.getNode(): any).props: any);
}

test('Return enabled item config', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 5;

    expect(copyLocaleToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        label: 'sulu_admin.copy_locale',
    }));
});

test('Return no item config if deprecated display_condition is not met', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(
        ['en', 'de'],
        {display_condition: '_permission.edit'}
    );

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    expect(toolbarItemConfig).toEqual(undefined);
    expect(log.warn).toBeCalledWith(expect.stringContaining('The "display_condition" option is deprecated'));
});

test('Return no item config if passed visible_condition is not met', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(
        ['en', 'de'],
        {visible_condition: '_permission.edit'}
    );

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    expect(toolbarItemConfig).toEqual(undefined);
    expect(log.warn).not.toBeCalled();
});

test('Return item config if passed visible_condition is met', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(
        ['en', 'de'],
        {visible_condition: '_permission.edit'}
    );

    copyLocaleToolbarAction.resourceFormStore.resourceStore.data._permission = {edit: true};
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 1;

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig)
        .toEqual(expect.objectContaining({disabled: false, label: 'sulu_admin.copy_locale', type: 'button'}));
});

test('Return disabled item config if an add form is shown', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de']);

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    expect(toolbarItemConfig.disabled).toEqual(true);
});

test('Return no dialog if no id is set', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(copyLocaleToolbarAction.getNode()).toEqual(null);
});

test('Throw error if no locale is given', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceStore.locale = undefined;

    expect(() => copyLocaleToolbarAction.getNode()).toThrow('locale');
});

test('Throw error if no available locales are given', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction();
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');

    expect(() => copyLocaleToolbarAction.getNode()).toThrow('locales');
});

test('Close dialog when cancel button of dialog is clicked', () => {
    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    copyLocaleToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
        open: true,
    }));

    getDialogProps(copyLocaleToolbarAction).onCancel();
    expect(copyLocaleToolbarAction.form.showSuccessSnackbar).not.toBeCalledWith();
    expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Close dialog and show success message when onClose from CopyLocaleDialog is called with true', async() => {
    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de', 'fr']);
    copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    copyLocaleToolbarAction.resourceFormStore.resourceKey = 'snippets';
    const locale = copyLocaleToolbarAction.resourceFormStore.resourceStore.locale;
    // $FlowFixMe
    locale.get.mockReturnValue('en');
    copyLocaleToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }

    const clickHandler = toolbarItemConfig.onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    await new Promise((resolve) => setTimeout(resolve, 1));
    copyLocaleToolbarAction.formStore.change('locales', ['de', 'fr']);

    expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
        open: true,
    }));

    getDialogProps(copyLocaleToolbarAction).onConfirm();
    expect(ResourceRequester.post).toBeCalledWith(
        'snippets',
        undefined,
        {action: 'copy-locale', dest: ['de', 'fr'], id: 3, locale, webspace: 'sulu_io'}
    );

    await postPromise;
    expect(copyLocaleToolbarAction.form.showSuccessSnackbar).toBeCalledWith();
    expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
        open: false,
    }));
});

test(
    'Close dialog and show success message when onClose from CopyLocaleDialog is called with true ' +
    '(with additional fields)',
    async() => {
        const formMetadata = {
            ...FORM,
            title: {
                label: 'Test',
                type: 'text_line',
            },
        };
        metadataStore.getSchema.mockReturnValue(Promise.resolve(formMetadata));

        const postPromise = Promise.resolve();
        ResourceRequester.post.mockReturnValue(postPromise);

        const copyLocaleToolbarAction = createCopyLocaleToolbarAction(['en', 'de', 'fr']);
        copyLocaleToolbarAction.resourceFormStore.resourceStore.id = 3;
        // $FlowFixMe
        copyLocaleToolbarAction.resourceFormStore.resourceKey = 'snippets';
        const locale = copyLocaleToolbarAction.resourceFormStore.resourceStore.locale;
        // $FlowFixMe
        locale.get.mockReturnValue('en');
        copyLocaleToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

        const toolbarItemConfig = copyLocaleToolbarAction.getToolbarItemConfig();
        if (!toolbarItemConfig) {
            throw new Error('The toolbarItemConfig should be a value!');
        }

        const clickHandler = toolbarItemConfig.onClick;
        if (!clickHandler) {
            throw new Error('A onClick callback should be registered on the copy locale option');
        }

        expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
            open: false,
        }));

        clickHandler();
        await new Promise((resolve) => setTimeout(resolve, 1));
        copyLocaleToolbarAction.formStore.change('locales', ['de', 'fr']);
        copyLocaleToolbarAction.formStore.change('title', 'Test 123');

        expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
            open: true,
        }));

        getDialogProps(copyLocaleToolbarAction).onConfirm();
        expect(ResourceRequester.post).toBeCalledWith(
            'snippets',
            undefined,
            {action: 'copy-locale', dest: ['de', 'fr'], id: 3, locale, webspace: 'sulu_io', title: 'Test 123'}
        );

        await postPromise;
        expect(copyLocaleToolbarAction.form.showSuccessSnackbar).toBeCalledWith();
        expect(getDialogProps(copyLocaleToolbarAction)).toEqual(expect.objectContaining({
            open: false,
        }));
    }
);
