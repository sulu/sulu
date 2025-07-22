// @flow
import {mount} from 'enzyme';
import {observable} from 'mobx';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import List from '../../../../views/List';
import Dialog from '../../../../components/Dialog';
import {ResourceRequester} from '../../../../services';
import RestoreVersionItemAction from '../../itemActions/RestoreVersionItemAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.attributes = {};
    this.navigate = jest.fn();
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

    return new RestoreVersionItemAction(listStore, list, router, undefined, undefined, options);
}

test('Return disabled item action config without callback if no item is given', () => {
    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});

    expect(itemAction.getItemActionConfig({version: 1234567})).toEqual(expect.objectContaining({
        disabled: false,
        onClick: expect.anything(),
    }));

    expect(itemAction.getItemActionConfig(undefined)).toEqual(expect.objectContaining({
        disabled: true,
        onClick: undefined,
    }));
});

test('Display dialog if onClick callback is fired', () => {
    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});

    let dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props()).toEqual(expect.objectContaining({
        open: false,
        cancelText: 'sulu_admin.cancel',
        confirmText: 'sulu_admin.ok',
        title: 'sulu_page.restore_version',
    }));

    const onClick = itemAction.getItemActionConfig({version: 1234567}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick(1234567, 1);

    dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props()).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog if it is canceled', () => {
    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});

    const onClick = itemAction.getItemActionConfig({version: 1234567}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick(1234567, 1);

    let dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props().open).toBeTruthy();

    dialog.props().onCancel();

    dialog = mount(itemAction.getNode()).find(Dialog);
    expect(dialog.props().open).toBeFalsy();
});

test('Send request and navigate to "success_view" if dialog is confirmed', () => {
    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});
    itemAction.router.attributes = {id: 'page-id', locale: 'de', webspace: 'sulu'};

    const onClick = itemAction.getItemActionConfig({version: 1234567}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick(1234567, 1);
    mount(itemAction.getNode()).find(Dialog).props().onConfirm();

    expect(ResourceRequester.post).toBeCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', version: 1234567, id: 'page-id', locale: 'de', webspace: 'sulu'}
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
        expect(itemAction.router.navigate).toBeCalledWith(
            'sulu_page.page_edit_form',
            {id: 'page-id', locale: 'de', webspace: 'sulu'}
        );
    });
});

test('Throw error when dialog is confirmed if given "success_view" option is not a string', () => {
    const itemAction = createItemAction({});
    const onClick = itemAction.getItemActionConfig({version: 1234567}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick(1234567, 1);

    const dialog = mount(itemAction.getNode()).find(Dialog);

    expect(() => dialog.props().onConfirm()).toThrow(/success_view/);
});
