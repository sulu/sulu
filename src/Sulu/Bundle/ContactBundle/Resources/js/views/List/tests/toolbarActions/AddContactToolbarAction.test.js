// @flow
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import {ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {List} from 'sulu-admin-bundle/views';
import AddContactToolbarAction from '../../toolbarActions/AddContactToolbarAction';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.options = {};
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    put: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore/ResourceStore', () => jest.fn(function() {
    this.data = {};
    this.setMultiple = jest.fn();
}));

function createAddContactToolbarAction() {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new AddContactToolbarAction(listStore, list, router, locales, resourceStore, {});
}

test('Return config for toolbar item', () => {
    const addContactToolbarAction = createAddContactToolbarAction();

    expect(addContactToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-plus-circle',
        label: 'sulu_admin.add',
        type: 'button',
    }));
});

test('Open dialog if button is clicked', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    expect(shallow(addContactToolbarAction.getNode()).instance().props.open).toEqual(false);
    clickHandler();
    expect(shallow(addContactToolbarAction.getNode()).instance().props.open).toEqual(true);
});

test('Pass correct options to components', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;
    clickHandler();

    const node = shallow(addContactToolbarAction.getNode());
    expect(node.find('ResourceSingleSelect').prop('editable')).toEqual(true);
    expect(node.find('SingleAutoComplete').prop('options')).toEqual({excludedAccountId: 4, flat: false});
});

test('Reset fields if overlay is just closed', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    clickHandler();
    let addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.instance().props.open).toEqual(true);

    addContactOverlay.find('SingleAutoComplete').prop('onChange')({id: 3});
    addContactOverlay.find('ResourceSingleSelect').prop('onChange')(5);

    addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.find('SingleAutoComplete').prop('value')).toEqual({id: 3});
    expect(addContactOverlay.find('ResourceSingleSelect').prop('value')).toEqual(5);
    addContactOverlay.instance().props.onClose();

    addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.instance().props.open).toEqual(false);

    clickHandler();
    addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.find('SingleAutoComplete').prop('value')).toEqual(undefined);
    expect(addContactOverlay.find('ResourceSingleSelect').prop('value')).toEqual(undefined);

    expect(ResourceRequester.put).not.toBeCalled();
});

test('Add selected contact to current account', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    const putPromise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(putPromise);

    clickHandler();
    let addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.instance().props.open).toEqual(true);
    expect(addContactOverlay.instance().props.confirmDisabled).toEqual(true);
    addContactOverlay.find('SingleAutoComplete').prop('onChange')({id: 3});

    addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.instance().props.confirmDisabled).toEqual(false);

    addContactOverlay.instance().props.onConfirm();

    addContactOverlay = shallow(addContactToolbarAction.getNode()).instance();
    expect(addContactOverlay.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.put).toBeCalledWith('account_contacts', {position: undefined}, {accountId: 4, id: 3});

    return putPromise.then(() => {
        addContactOverlay = shallow(addContactToolbarAction.getNode()).instance();
        expect(addContactOverlay.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = addContactToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addContactToolbarAction.listStore.reload).toBeCalledWith();
    });
});

test('Add selected contact to current account with position', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    const putPromise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(putPromise);

    clickHandler();
    let addContactOverlay = shallow(addContactToolbarAction.getNode());
    expect(addContactOverlay.instance().props.open).toEqual(true);
    addContactOverlay.find('SingleAutoComplete').prop('onChange')({id: 3});
    addContactOverlay.find('ResourceSingleSelect').prop('onChange')(5);

    addContactOverlay.instance().props.onConfirm();

    addContactOverlay = shallow(addContactToolbarAction.getNode()).instance();
    expect(addContactOverlay.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.put).toBeCalledWith('account_contacts', {position: 5}, {accountId: 4, id: 3});

    return putPromise.then(() => {
        addContactOverlay = shallow(addContactToolbarAction.getNode()).instance();
        expect(addContactOverlay.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = addContactToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addContactToolbarAction.listStore.reload).toBeCalledWith();
    });
});
