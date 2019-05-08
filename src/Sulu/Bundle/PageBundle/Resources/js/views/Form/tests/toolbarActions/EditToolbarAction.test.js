// @flow
import {mount} from 'enzyme';
import {ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import Form from 'sulu-admin-bundle/views/Form/Form';
import EditToolbarAction from '../../toolbarActions/EditToolbarAction';

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.id = undefined;
        this.data = {};
        this.locale = {
            get: jest.fn(),
        };
    }),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceFormStore: class {
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
    },
}));

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        post: jest.fn(),
    },
    Router: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/views/Form/Form', () => jest.fn(function() {
    this.showSuccessSnackbar = jest.fn();
}));

function createEditToolbarAction(locales) {
    const resourceStore = new ResourceStore('test');
    const formStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new EditToolbarAction(formStore, form, router, locales);
}

test('Return enabled item config', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 5;
    editToolbarAction.resourceFormStore.resourceStore.data.published = true;
    editToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    expect(editToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-pen',
        label: 'sulu_admin.edit',
        type: 'dropdown',
        options: [
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.copy_locale',
            }),
            expect.objectContaining({
                disabled: false,
                label: 'sulu_page.delete_draft',
            }),
            expect.objectContaining({
                disabled: false,
                label: 'sulu_page.unpublish',
            }),
        ],
    }));
});

test('Return disabled delete draft and unpublish items when page is not published', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 5;
    editToolbarAction.resourceFormStore.resourceStore.data.published = false;
    editToolbarAction.resourceFormStore.resourceStore.data.publishedState = false;

    expect(editToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-pen',
        label: 'sulu_admin.edit',
        type: 'dropdown',
        options: [
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.copy_locale',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_page.delete_draft',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_page.unpublish',
            }),
        ],
    }));
});

test('Return disabled delete draft item when page has no draft', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 5;
    editToolbarAction.resourceFormStore.resourceStore.data.published = true;
    editToolbarAction.resourceFormStore.resourceStore.data.publishedState = true;

    expect(editToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-pen',
        label: 'sulu_admin.edit',
        type: 'dropdown',
        options: [
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.copy_locale',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_page.delete_draft',
            }),
            expect.objectContaining({
                disabled: false,
                label: 'sulu_page.unpublish',
            }),
        ],
    }));
});

test('Return disabled item config', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(editToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-pen',
        label: 'sulu_admin.edit',
        type: 'dropdown',
        options: [
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.copy_locale',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_page.delete_draft',
            }),
            expect.objectContaining({
                disabled: true,
                label: 'sulu_page.unpublish',
            }),
        ],
    }));
});

test('Return no dialog if no id is set', () => {
    const editToolbarAction = createEditToolbarAction(['en']);
    editToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(editToolbarAction.getNode()).toEqual(null);
});

test('Throw error if no locale is given', () => {
    const editToolbarAction = createEditToolbarAction(['en']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale = undefined;

    expect(() => editToolbarAction.getNode()).toThrow('locale');
});

test('Throw error if no available locales are given', () => {
    const editToolbarAction = createEditToolbarAction();
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');

    expect(() => editToolbarAction.getNode()).toThrow('locales');
});

test('Throw error if no webspace is given', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');

    expect(() => editToolbarAction.getNode()).toThrow('webspace');
});

test('Pass correct props to CopyLocaleDialog', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    editToolbarAction.resourceFormStore.resourceStore.data.availableLocales = ['en'];
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[0].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    const element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        availableLocales: ['en'],
        id: 3,
        locale: 'en',
        locales: ['en', 'de'],
        open: false,
        webspace: 'sulu_io',
    }));
});

test('Close dialog when onClose from CopyLocaleDialog is called', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[0].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.instance().props.onClose(false);
    expect(editToolbarAction.form.showSuccessSnackbar).not.toBeCalledWith();
    element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Close dialog and show success message when onClose from CopyLocaleDialog is called with true', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[0].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.instance().props.onClose(true);
    expect(editToolbarAction.form.showSuccessSnackbar).toBeCalledWith();
    element = mount(editToolbarAction.getNode()).at(0);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Close dialog when onClose from delete draft dialog is called', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[1].onClick;
    if (!clickHandler) {
        throw new Error('An onClick callback should be registered on the delete draft option');
    }

    let element = mount(editToolbarAction.getNode()).at(1);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(editToolbarAction.getNode()).at(1);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.prop('onCancel')();
    element = mount(editToolbarAction.getNode()).at(1);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Delete draft when delete draft dialog is confirmed', () => {
    const data = {
        title: 'Title',
    };

    const deleteDraftPromise = Promise.resolve(data);
    ResourceRequester.post.mockReturnValue(deleteDraftPromise);

    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[1].onClick;
    if (!clickHandler) {
        throw new Error('An onClick callback should be registered on the delete draft option');
    }

    let element = mount(editToolbarAction.getNode()).at(1);
    clickHandler();

    expect(element.prop('confirmLoading')).toEqual(false);
    element.prop('onConfirm')();
    element = mount(editToolbarAction.getNode()).at(1);
    expect(element.prop('confirmLoading')).toEqual(true);
    expect(ResourceRequester.post).toBeCalledWith(
        'pages',
        undefined,
        {action: 'remove-draft', id: 3, locale: editToolbarAction.resourceFormStore.locale, webspace: 'sulu_io'}
    );

    return deleteDraftPromise.then(() => {
        element = mount(editToolbarAction.getNode()).at(1);
        expect(element.prop('confirmLoading')).toEqual(false);
        expect(editToolbarAction.resourceFormStore.setMultiple).toBeCalledWith(data);
        expect(editToolbarAction.resourceFormStore.dirty).toEqual(false);
    });
});

test('Close dialog when onClose from unpublish dialog is called', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[2].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(editToolbarAction.getNode()).at(2);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = mount(editToolbarAction.getNode()).at(2);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.prop('onCancel')();
    element = mount(editToolbarAction.getNode()).at(2);
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Unpublish page when delete draft dialog is confirmed', () => {
    const data = {
        title: 'Title',
    };

    const unpublishPromise = Promise.resolve(data);
    ResourceRequester.post.mockReturnValue(unpublishPromise);

    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.resourceFormStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.resourceFormStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.resourceFormStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[2].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the unpublish option');
    }

    let element = mount(editToolbarAction.getNode()).at(2);
    clickHandler();

    expect(element.prop('confirmLoading')).toEqual(false);
    element.prop('onConfirm')();
    element = mount(editToolbarAction.getNode()).at(2);
    expect(element.prop('confirmLoading')).toEqual(true);
    expect(ResourceRequester.post).toBeCalledWith(
        'pages',
        undefined,
        {action: 'unpublish', id: 3, locale: editToolbarAction.resourceFormStore.locale, webspace: 'sulu_io'}
    );

    return unpublishPromise.then(() => {
        element = mount(editToolbarAction.getNode()).at(2);
        expect(element.prop('confirmLoading')).toEqual(false);
        expect(editToolbarAction.resourceFormStore.setMultiple).toBeCalledWith(data);
        expect(editToolbarAction.resourceFormStore.dirty).toEqual(false);
    });
});
