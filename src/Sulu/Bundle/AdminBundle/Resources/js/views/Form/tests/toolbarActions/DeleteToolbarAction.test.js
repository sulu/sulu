// @flow
import {mount} from 'enzyme';
import {observable} from 'mobx';
import DeleteToolbarAction from '../../toolbarActions/DeleteToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.id = id;
    this.observableOptions = observableOptions;
}));

jest.mock('../../../../containers/Form', () => ({
    ResourceFormStore: class {
        data = {};
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        get locale() {
            return this.resourceStore.observableOptions.locale;
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

function createDeleteToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test', undefined, {locale: observable.box('en')});
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });
    return new DeleteToolbarAction(resourceFormStore, form, router, [], options);
}

test('Return item config with correct disabled, loading, icon, type and value and return closed dialog', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 5;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        type: 'button',
    }));

    const element = mount(deleteToolbarAction.getNode());
    expect(element.at(0).instance().props).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_admin.delete_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_admin.delete_warning_title',
    }));
    expect(element.at(1).instance().props).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_admin.delete_linked_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_admin.delete_linked_warning_title',
    }));
});

test('Return item config with disabled button if an add form is opened', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = undefined;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return empty item config when passed condition is not met', () => {
    const deleteToolbarAction = createDeleteToolbarAction({display_condition: 'url == "/"'});

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(undefined);
});

test('Return item config when passed condition is met', () => {
    const deleteToolbarAction = createDeleteToolbarAction({display_condition: 'url == "/"'});
    deleteToolbarAction.resourceFormStore.data.url = '/';

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({label: 'sulu_admin.delete'}));
});

test('Open dialog on toolbar item click', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    const element = mount(deleteToolbarAction.getNode());
    expect(element.at(0).instance().props).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog on cancel click', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = mount(deleteToolbarAction.getNode());
    expect(element.at(0).instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Button[skin="secondary"]').simulate('click');
    element = mount(deleteToolbarAction.getNode());
    expect(element.at(0).instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when dialog is confirmed', () => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backRoute = 'sulu_test.list';

    const deletePromise = Promise.resolve();
    deleteToolbarAction.resourceFormStore.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = mount(deleteToolbarAction.getNode());
    expect(element.at(0).instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Button[skin="primary"]').simulate('click');
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith();

    return deletePromise.then(() => {
        element = mount(deleteToolbarAction.getNode());
        expect(deleteToolbarAction.router.navigate).toBeCalledWith('sulu_test.list', {locale: 'en'});
        expect(element.at(0).instance().props).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});

test('Call delete with force when dialog is confirmed twice', (done) => {
    const deleteToolbarAction = createDeleteToolbarAction();
    deleteToolbarAction.resourceFormStore.resourceStore.id = 3;
    deleteToolbarAction.router.route.options.backRoute = 'sulu_test.list';

    const deletePromise = Promise.reject({status: 409});
    deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

    const toolbarItemConfig = deleteToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();

    let element = mount(deleteToolbarAction.getNode());
    expect(element.at(0).instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Button[skin="primary"]').simulate('click');
    expect(deleteToolbarAction.resourceFormStore.delete).toBeCalledWith();

    setTimeout(() => {
        element = mount(deleteToolbarAction.getNode());
        expect(deleteToolbarAction.router.navigate).toBeCalledTimes(0);
        expect(element.at(0).instance().props).toEqual(expect.objectContaining({
            open: false,
        }));
        expect(element.at(1).instance().props).toEqual(expect.objectContaining({
            open: true,
        }));

        const deletePromise = Promise.resolve({});
        deleteToolbarAction.resourceFormStore.delete.mockReturnValueOnce(deletePromise);

        element.find('Button[skin="primary"]').simulate('click');

        setTimeout(() => {
            expect(deleteToolbarAction.router.navigate).toBeCalledWith('sulu_test.list', {locale: 'en'});
            expect(element.at(0).instance().props).toEqual(expect.objectContaining({
                open: false,
            }));

            done();
        });
    });
});
