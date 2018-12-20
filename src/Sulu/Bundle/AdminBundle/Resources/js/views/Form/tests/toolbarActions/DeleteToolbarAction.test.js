// @flow
import {shallow} from 'enzyme';
import DeleteToolbarAction from '../../toolbarActions/DeleteToolbarAction';
import {FormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id) {
    this.id = id;
    this.locale = {
        get: jest.fn(),
    };

    this.setLocale = jest.fn((locale) => {
        this.locale.get.mockReturnValue(locale);
    });
}));

jest.mock('../../../../containers/Form', () => ({
    FormStore: class {
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get locale() {
            return this.resourceStore.locale;
        }

        delete = jest.fn();
    },
}));

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
    this.route = {
        options: {},
    };
}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createDeleteToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const formStore = new FormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });
    return new DeleteToolbarAction(formStore, form, router);
}

test('Return item config with correct disabled, loading, icon, type and value and return closed dialog', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.formStore.resourceStore.id = 5;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        type: 'button',
    }));

    const element = shallow(deleteToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_admin.delete_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_admin.delete_warning_title',
    }));
});

test('Return item config with disabled button if an add form is opened', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.formStore.resourceStore.id = undefined;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Open dialog on toolbar item click', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.formStore.resourceStore.id = 3;

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    const element = shallow(deleteToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog on cancel click', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.formStore.resourceStore.id = 3;

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let element = shallow(deleteToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Button[skin="secondary"]').simulate('click');
    element = shallow(deleteToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when dialog is confirmed', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.formStore.resourceStore.id = 3;
    deleteToolbarAction.formStore.resourceStore.setLocale('en');
    deleteToolbarAction.router.route.options.backRoute = 'sulu_test.datagrid';

    const deletePromise = Promise.resolve();
    deleteToolbarAction.formStore.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let element = shallow(deleteToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Button[skin="primary"]').simulate('click');
    expect(deleteToolbarAction.formStore.delete).toBeCalledWith();

    return deletePromise.then(() => {
        element = shallow(deleteToolbarAction.getNode());
        expect(deleteToolbarAction.router.navigate).toBeCalledWith('sulu_test.datagrid', {locale: 'en'});
        expect(element.instance().props).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});
