// @flow
import mockReact from 'react';
import {act, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import Router from 'sulu-admin-bundle/services/Router';
import List from 'sulu-admin-bundle/views/List';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import RestoreItemAction from '../../itemActions/RestoreItemAction';

jest.mock('sulu-admin-bundle/utils/Translator');

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

function mockRestoreFormOverlay(props) {
    if (!props.open) {
        return null;
    }

    return mockReact.createElement(
        'div',
        {},
        mockReact.createElement('div', {}, 'restore form overlay mock'),
        mockReact.createElement('div', {}, props.formKey),
        mockReact.createElement('div', {}, props.trashItemId),
        mockReact.createElement(
            'button',
            {
                onClick: props.onClose,
                type: 'button',
            },
            'close restore form overlay'
        ),
        mockReact.createElement(
            'button',
            {
                disabled: props.confirmLoading,
                onClick: () => props.onConfirm({foo: 'bar'}),
                type: 'button',
            },
            'confirm restore form overlay'
        )
    );
}

jest.mock('../../../../containers/RestoreFormOverlay', () => jest.fn(mockRestoreFormOverlay));

beforeEach(() => {
    jest.clearAllMocks();
    RestoreItemAction.restoreConfigurationMapping = {};
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

function openItemActionDialog(
    itemAction: RestoreItemAction,
    rerender: (node: React$Node) => void,
    item: Object = {id: 'id-1234'}
) {
    const onClick = itemAction.getItemActionConfig(item).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }

    onClick(item.id, 1);
    rerender(itemAction.getNode());
}

function finishDialogCloseTransition() {
    const dialogContainer = document.querySelector('.dialogContainer');

    if (!(dialogContainer instanceof HTMLElement)) {
        throw new Error('Expected dialog container');
    }

    fireEvent.transitionEnd(dialogContainer);
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
    const {rerender} = render(itemAction.getNode());

    expect(screen.queryByText('sulu_trash.restore_element')).not.toBeInTheDocument();

    openItemActionDialog(itemAction, rerender);

    expect(screen.getByText('sulu_trash.restore_element')).toBeInTheDocument();
    expect(screen.getByText('sulu_trash.restore_element_dialog_text')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
});

test('Close dialog if it is canceled', async() => {
    const user = userEvent.setup();
    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    openItemActionDialog(itemAction, rerender);

    expect(screen.getByText('sulu_trash.restore_element')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_trash.restore_element')).not.toBeInTheDocument();
});

test('Send request and reload list store if dialog is confirmed', async() => {
    const user = userEvent.setup();
    let resolvePost = () => {};
    const postPromise = new Promise((resolve) => {
        resolvePost = resolve;
    });
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    openItemActionDialog(itemAction, rerender);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', id: 'id-1234'}
    );
    rerender(itemAction.getNode());
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolvePost();
        await postPromise;
    });
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_trash.restore_element')).not.toBeInTheDocument();
    expect(itemAction.listStore.reload).toHaveBeenCalledWith();
});

test('Send request and navigate to view if dialog is confirmed and view is configured', async() => {
    const user = userEvent.setup();
    RestoreItemAction.restoreConfigurationMapping.test = {
        view: 'test-view',
        resultToView: {id: 'id'},
    };

    let resolvePost: (response?: Object) => void = () => {};
    const postPromise = new Promise((resolve) => {
        resolvePost = resolve;
    });
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    openItemActionDialog(itemAction, rerender, {id: 'id-1234', resourceKey: 'test'});
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', id: 'id-1234'}
    );
    rerender(itemAction.getNode());
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolvePost({id: '1234-1234-1234', key: 'test-key'});
        await postPromise;
    });
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_trash.restore_element')).not.toBeInTheDocument();
    expect(itemAction.router.navigate).toHaveBeenLastCalledWith('test-view', {id: '1234-1234-1234'});
});

test('Display RestoreFormOverlay if onClick callback is fired', () => {
    RestoreItemAction.restoreConfigurationMapping.test = {form: 'foo'};
    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    expect(screen.queryByText('restore form overlay mock')).not.toBeInTheDocument();

    openItemActionDialog(itemAction, rerender, {id: 'id-1234', resourceKey: 'test'});

    expect(screen.getByText('restore form overlay mock')).toBeInTheDocument();
    expect(screen.getByText('foo')).toBeInTheDocument();
    expect(screen.getByText('id-1234')).toBeInTheDocument();
});

test('Close RestoreFormOverlay if it is canceled', async() => {
    const user = userEvent.setup();
    RestoreItemAction.restoreConfigurationMapping.test = {form: 'foo'};
    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    openItemActionDialog(itemAction, rerender, {id: 'id-1234', resourceKey: 'test'});

    expect(screen.getByText('restore form overlay mock')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'close restore form overlay'}));
    rerender(itemAction.getNode());

    expect(screen.queryByText('restore form overlay mock')).not.toBeInTheDocument();
});

test('Send request and reload list store if RestoreFormOverlay is confirmed', async() => {
    RestoreItemAction.restoreConfigurationMapping.test = {form: 'foo'};
    const user = userEvent.setup();
    let resolvePost = () => {};
    const postPromise = new Promise((resolve) => {
        resolvePost = resolve;
    });
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    openItemActionDialog(itemAction, rerender, {id: 'id-1234', resourceKey: 'test'});

    await user.click(screen.getByRole('button', {name: 'confirm restore form overlay'}));

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'list-resource-key',
        {foo: 'bar'},
        {action: 'restore', id: 'id-1234'}
    );
    rerender(itemAction.getNode());
    expect(screen.getByRole('button', {name: 'confirm restore form overlay'})).toBeDisabled();

    await act(async() => {
        resolvePost();
        await postPromise;
    });
    rerender(itemAction.getNode());

    expect(screen.queryByText('restore form overlay mock')).not.toBeInTheDocument();
    expect(itemAction.listStore.reload).toHaveBeenCalledWith();
});
