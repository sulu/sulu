// @flow
import mockReact from 'react';
import {observable} from 'mobx';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import Router from 'sulu-admin-bundle/services/Router';
import List from 'sulu-admin-bundle/views/List';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import RestoreItemAction from '../../itemActions/RestoreItemAction';

const React = mockReact;

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.attributes = {};
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(() => ({
        data: {},
    })),
}));

jest.mock('../../../../containers/RestoreFormOverlay', () => class RestoreFormOverlay extends mockReact.Component<*> {
    render() {
        return <div>restore form overlay mock</div>;
    }
});

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

function getNodeChildren(itemAction: RestoreItemAction): Array<any> {
    return React.Children.toArray(((itemAction.getNode(): any).props.children: any));
}

function getDialogProps(itemAction: RestoreItemAction): any {
    return ((getNodeChildren(itemAction)[0]: any).props: any);
}

function getRestoreOverlayProps(itemAction: RestoreItemAction): any {
    return ((getNodeChildren(itemAction)[1]: any).props: any);
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

    expect(getDialogProps(itemAction)).toEqual(expect.objectContaining({
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

    expect(getDialogProps(itemAction)).toEqual(expect.objectContaining({
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

    expect(getDialogProps(itemAction).open).toBeTruthy();

    getDialogProps(itemAction).onCancel();

    expect(getDialogProps(itemAction).open).toBeFalsy();
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
    getDialogProps(itemAction).onConfirm();

    expect(ResourceRequester.post).toBeCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', id: 'id-1234'}
    );
    expect(getDialogProps(itemAction)).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    return postPromise.then(() => {
        expect(getDialogProps(itemAction)).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));
        expect(itemAction.listStore.reload).toBeCalledWith();
    });
});

test('Send request and navigate to view if dialog is confirmed and view is configured', () => {
    RestoreItemAction.restoreConfigurationMapping.test = {
        view: 'test-view',
        resultToView: {id: 'id'},
    };

    const postPromise = Promise.resolve({id: '1234-1234-1234', key: 'test-key'});
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction();

    const onClick = itemAction.getItemActionConfig({id: 'id-1234', resourceKey: 'test'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);
    getDialogProps(itemAction).onConfirm();

    expect(ResourceRequester.post).toBeCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', id: 'id-1234'}
    );
    expect(getDialogProps(itemAction)).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    return postPromise.then(() => {
        expect(getDialogProps(itemAction)).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));
        expect(itemAction.router.navigate).toHaveBeenLastCalledWith('test-view', {id: '1234-1234-1234'});
    });
});

test('Display RestoreFormOverlay if onClick callback is fired', () => {
    RestoreItemAction.restoreConfigurationMapping.test = {form: 'foo'};
    const itemAction = createItemAction();

    expect(getRestoreOverlayProps(itemAction)).toEqual(expect.objectContaining({
        open: false,
    }));

    const onClick = itemAction.getItemActionConfig({id: 'id-1234', resourceKey: 'test'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);

    expect(getRestoreOverlayProps(itemAction)).toEqual(expect.objectContaining({
        open: true,
        formKey: 'foo',
        trashItemId: 'id-1234',
    }));
});

test('Close dialog if it is canceled', () => {
    RestoreItemAction.restoreConfigurationMapping.test = {form: 'foo'};
    const itemAction = createItemAction();

    const onClick = itemAction.getItemActionConfig({id: 'id-1234', resourceKey: 'test'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);

    expect(getRestoreOverlayProps(itemAction).open).toBeTruthy();

    getRestoreOverlayProps(itemAction).onClose();

    expect(getRestoreOverlayProps(itemAction).open).toBeFalsy();
});

test('Send request and reload list store if dialog is confirmed', () => {
    RestoreItemAction.restoreConfigurationMapping.test = {form: 'foo'};
    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction();

    const onClick = itemAction.getItemActionConfig({id: 'id-1234', resourceKey: 'test'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('id-1234', 1);

    const data = {foo: 'bar'};
    getRestoreOverlayProps(itemAction).onConfirm(data);

    expect(ResourceRequester.post).toBeCalledWith(
        'list-resource-key',
        data,
        {action: 'restore', id: 'id-1234'}
    );
    expect(getRestoreOverlayProps(itemAction)).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    return postPromise.then(() => {
        expect(getRestoreOverlayProps(itemAction)).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));
        expect(itemAction.listStore.reload).toBeCalledWith();
    });
});
