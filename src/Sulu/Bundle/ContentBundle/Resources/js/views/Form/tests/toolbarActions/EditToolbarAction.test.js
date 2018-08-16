// @flow
import {shallow} from 'enzyme';
import {Form, FormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {Router} from 'sulu-admin-bundle/services';
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
    Form: jest.fn(),
    FormStore: class {
        resourceStore;
        options = {};

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
    Router: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

function createEditToolbarAction(locales) {
    const formStore = new FormStore(new ResourceStore('test'));
    const form = new Form({
        onSubmit: jest.fn(),
        store: formStore,
    });
    const router = new Router({});

    return new EditToolbarAction(formStore, form, router, locales);
}

test('Return enabled item config', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.formStore.resourceStore.id = 5;

    expect(editToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-pen',
        label: 'sulu_admin.edit',
        type: 'dropdown',
        options: [
            expect.objectContaining({
                disabled: false,
                label: 'sulu_admin.copy_locale',
            }),
        ],
    }));
});

test('Return disabled item config', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.formStore.resourceStore.id = undefined;

    expect(editToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-pen',
        label: 'sulu_admin.edit',
        type: 'dropdown',
        options: [
            expect.objectContaining({
                disabled: true,
                label: 'sulu_admin.copy_locale',
            }),
        ],
    }));
});

test('Return no dialog if no id is set', () => {
    const editToolbarAction = createEditToolbarAction(['en']);
    editToolbarAction.formStore.resourceStore.id = undefined;

    expect(editToolbarAction.getNode()).toEqual(undefined);
});

test('Throw error if no locale is given', () => {
    const editToolbarAction = createEditToolbarAction(['en']);
    editToolbarAction.formStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.formStore.resourceStore.locale = undefined;

    expect(() => editToolbarAction.getNode()).toThrow('locale');
});

test('Throw error if no available locales are given', () => {
    const editToolbarAction = createEditToolbarAction();
    editToolbarAction.formStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.formStore.resourceStore.locale.get.mockReturnValue('en');

    expect(() => editToolbarAction.getNode()).toThrow('locales');
});

test('Throw error if no webspace is given', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.formStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.formStore.resourceStore.locale.get.mockReturnValue('en');

    expect(() => editToolbarAction.getNode()).toThrow('webspace');
});

test('Pass correct props to CopyLocaleDialog', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.formStore.resourceStore.id = 3;
    editToolbarAction.formStore.resourceStore.data.concreteLanguages = ['en'];
    // $FlowFixMe
    editToolbarAction.formStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.formStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[0].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    const element = shallow(editToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        concreteLocales: ['en'],
        id: 3,
        locale: 'en',
        locales: ['en', 'de'],
        open: false,
        webspace: 'sulu_io',
    }));
});

test('Close dialog when onClose from CopyLocaleDialog is called', () => {
    const editToolbarAction = createEditToolbarAction(['en', 'de']);
    editToolbarAction.formStore.resourceStore.id = 3;
    // $FlowFixMe
    editToolbarAction.formStore.resourceStore.locale.get.mockReturnValue('en');
    editToolbarAction.formStore.options.webspace = 'sulu_io';

    const toolbarItemConfig = editToolbarAction.getToolbarItemConfig();
    const clickHandler = toolbarItemConfig.options[0].onClick;
    if (!clickHandler) {
        throw new Error('A onClick callback should be registered on the copy locale option');
    }

    let element = shallow(editToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));

    clickHandler();
    element = shallow(editToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.instance().props.onClose();
    element = shallow(editToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});
