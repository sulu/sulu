// @flow
import {mount} from 'enzyme';
import {observable} from 'mobx';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import Router from 'sulu-admin-bundle/services/Router';
import List from 'sulu-admin-bundle/views/List';
import Dialog from 'sulu-admin-bundle/components/Dialog';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import RestoreItemAction from '../../itemActions/RestoreItemAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.setShouldReload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.attributes = {};
}));

function createItemAction(options = {}) {
    const router = new Router({});
    const listStore = new ListStore(
        'list-resource-key',
        'list-key',
        'settings-key',
        {page: observable.box(1)}
    );
    const list = new List({
        route: router.route,
        router,
    });

    return new RestoreItemAction(listStore, list, router, undefined, undefined, options);
}

test('Return disabled item action config without callback if no item is given', () => {
    const itemAction = createItemAction();

    expect(itemAction.getItemActionConfig({id: 'id-1234'})).toEqual(expect.objectContaining({
        disabled: false,
        onClick: expect.anything(),
    }));

    expect(itemAction.getItemActionConfig(undefined)).toEqual(expect.objectContaining({
        disabled: true,
        onClick: undefined,
    }));
});

test('Display dialog if onClick callback is fired', () => {
    const itemAction = createItemAction();

    let dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props()).toEqual(expect.objectContaining({
        open: false,
        cancelText: 'sulu_admin.cancel',
        confirmText: 'sulu_admin.ok',
        title: 'sulu_trash.restore_element',
    }));

    const onClick = itemAction.getItemActionConfig({id: 'id-1234'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);

    dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props()).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog if it is canceled', () => {
    const itemAction = createItemAction();

    const onClick = itemAction.getItemActionConfig({id: 'id-1234'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);

    let dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props().open).toBeTruthy();

    dialog.props().onCancel();

    dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props().open).toBeFalsy();
});

test('Send request and reload list store if dialog is confirmed', () => {
    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction();

    const onClick = itemAction.getItemActionConfig({id: 'id-1234'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);
    mount(itemAction.getNode()).find(Dialog).props().onConfirm();

    expect(ResourceRequester.post).toBeCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', id: 'id-1234'}
    );
    expect(mount(itemAction.getNode()).find(Dialog).props()).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    return postPromise.then(() => {
        expect(mount(itemAction.getNode()).find(Dialog).props()).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));
        expect(itemAction.listStore.setShouldReload).toBeCalledWith(true);
    });
});
