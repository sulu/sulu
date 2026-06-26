// @flow
import {observable} from 'mobx';
import {ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {findElementByType} from 'sulu-admin-bundle/utils/TestHelper';
import {List} from 'sulu-admin-bundle/views';
import AddContactToolbarAction from '../../toolbarActions/AddContactToolbarAction';

jest.mock('sulu-admin-bundle/utils/Translator');

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

function getOverlayProps(addContactToolbarAction) {
    return addContactToolbarAction.getNode().props;
}

function getResourceSingleSelectProps(addContactToolbarAction) {
    return findElementByType(addContactToolbarAction.getNode(), 'ResourceSingleSelect').props;
}

function getSingleAutoCompleteProps(addContactToolbarAction) {
    return findElementByType(addContactToolbarAction.getNode(), 'SingleAutoComplete').props;
}

function confirmOverlay(addContactToolbarAction) {
    const {onConfirm} = getOverlayProps(addContactToolbarAction);

    if (!onConfirm) {
        throw new Error('Overlay confirm handler must be defined!');
    }

    onConfirm();
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

    expect(getOverlayProps(addContactToolbarAction).open).toEqual(false);
    clickHandler();
    expect(getOverlayProps(addContactToolbarAction).open).toEqual(true);
});

test('Pass correct options to components', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;
    clickHandler();

    expect(getResourceSingleSelectProps(addContactToolbarAction).editable).toEqual(true);
    expect(getSingleAutoCompleteProps(addContactToolbarAction).options).toEqual({excludedAccountId: 4, flat: false});
});

test('Reset fields if overlay is just closed', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    clickHandler();
    expect(getOverlayProps(addContactToolbarAction).open).toEqual(true);

    getSingleAutoCompleteProps(addContactToolbarAction).selectionStore.set({id: 3});
    getResourceSingleSelectProps(addContactToolbarAction).onChange(5);

    expect(getSingleAutoCompleteProps(addContactToolbarAction).selectionStore.item).toEqual({id: 3});
    expect(getResourceSingleSelectProps(addContactToolbarAction).value).toEqual(5);
    getOverlayProps(addContactToolbarAction).onClose();

    expect(getOverlayProps(addContactToolbarAction).open).toEqual(false);

    clickHandler();
    expect(getSingleAutoCompleteProps(addContactToolbarAction).selectionStore.item).toEqual(undefined);
    expect(getResourceSingleSelectProps(addContactToolbarAction).value).toEqual(undefined);

    expect(ResourceRequester.put).not.toHaveBeenCalled();
});

test('Add selected contact to current account', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    const putPromise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(putPromise);

    clickHandler();
    expect(getOverlayProps(addContactToolbarAction).open).toEqual(true);
    expect(getOverlayProps(addContactToolbarAction).confirmDisabled).toEqual(true);
    getSingleAutoCompleteProps(addContactToolbarAction).selectionStore.set({id: 3});

    expect(getOverlayProps(addContactToolbarAction).confirmDisabled).toEqual(false);

    confirmOverlay(addContactToolbarAction);

    expect(getOverlayProps(addContactToolbarAction)).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.put).toHaveBeenCalledWith('account_contacts', {position: undefined}, {
        accountId: 4,
        id: 3,
    });

    return putPromise.then(() => {
        expect(getOverlayProps(addContactToolbarAction)).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = addContactToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addContactToolbarAction.listStore.reload).toHaveBeenCalledWith();
    });
});

test('Add selected contact to current account with position', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    const putPromise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(putPromise);

    clickHandler();
    expect(getOverlayProps(addContactToolbarAction).open).toEqual(true);
    getSingleAutoCompleteProps(addContactToolbarAction).selectionStore.set({id: 3});
    getResourceSingleSelectProps(addContactToolbarAction).onChange(5);

    confirmOverlay(addContactToolbarAction);

    expect(getOverlayProps(addContactToolbarAction)).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.put).toHaveBeenCalledWith('account_contacts', {position: 5}, {accountId: 4, id: 3});

    return putPromise.then(() => {
        expect(getOverlayProps(addContactToolbarAction)).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = addContactToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addContactToolbarAction.listStore.reload).toHaveBeenCalledWith();
    });
});
