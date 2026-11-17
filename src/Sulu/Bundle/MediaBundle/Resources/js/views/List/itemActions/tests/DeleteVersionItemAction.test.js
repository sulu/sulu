// @flow
import {act, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import Router from 'sulu-admin-bundle/services/Router';
import List from 'sulu-admin-bundle/views/List';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import DeleteVersionItemAction from '../DeleteVersionItemAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function(resourceKey) {
    this.resourceKey = resourceKey;
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn(function() {
    this.showSuccessSnackbar = jest.fn();
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.attributes = {};
}));

function createItemAction() {
    const router = new Router({});
    const listStore = new ListStore('media_versions', 'media_versions', 'list', {page: observable.box(1)});
    const list = new List({route: router.route, router});

    return new DeleteVersionItemAction(listStore, list, router, undefined, undefined, {});
}

function openDialog(itemAction: DeleteVersionItemAction, rerender: (node: React$Node) => void) {
    const onClick = itemAction.getItemActionConfig({version: 2, current: false}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }

    onClick(2, 0);
    rerender(itemAction.getNode());
}

function finishDialogCloseTransition() {
    const dialogContainer = document.querySelector('.dialogContainer');

    if (!(dialogContainer instanceof HTMLElement)) {
        throw new Error('Expected dialog container');
    }

    fireEvent.transitionEnd(dialogContainer);
}

test('Return a delete item action config for an old version', () => {
    const itemAction = createItemAction();

    expect(itemAction.getItemActionConfig({version: 2, current: false})).toEqual(expect.objectContaining({
        icon: 'su-trash-alt',
        disabled: false,
        onClick: expect.anything(),
    }));
});

test('Return a locked item action config for the current version', () => {
    const itemAction = createItemAction();

    expect(itemAction.getItemActionConfig({version: 3, current: true})).toEqual({
        icon: 'su-lock',
        disabled: true,
        onClick: undefined,
    });
});

test('Return disabled item action config without callback if no item is given', () => {
    const itemAction = createItemAction();

    expect(itemAction.getItemActionConfig(undefined)).toEqual(expect.objectContaining({
        disabled: true,
        onClick: undefined,
    }));
});

test('Close dialog if it is canceled', async() => {
    const user = userEvent.setup();
    const itemAction = createItemAction();
    const {rerender} = render(itemAction.getNode());

    expect(screen.queryByText('sulu_admin.delete_warning_title')).not.toBeInTheDocument();

    openDialog(itemAction, rerender);
    expect(screen.getByText('sulu_admin.delete_warning_title')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_admin.delete_warning_title')).not.toBeInTheDocument();
    expect(ResourceRequester.delete).not.toHaveBeenCalled();
});

test('Delete the version, reload the list and show success if dialog is confirmed', async() => {
    const user = userEvent.setup();
    let resolveDelete = () => {};
    const deletePromise = new Promise((resolve) => {
        resolveDelete = resolve;
    });
    ResourceRequester.delete.mockReturnValue(deletePromise);

    const itemAction = createItemAction();
    itemAction.router.attributes = {id: 5, locale: 'de'};
    const {rerender} = render(itemAction.getNode());

    openDialog(itemAction, rerender);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.delete).toHaveBeenCalledWith('media_versions', {id: 5, locale: 'de', version: 2});
    rerender(itemAction.getNode());
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolveDelete();
        await deletePromise;
    });
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_admin.delete_warning_title')).not.toBeInTheDocument();
    expect(itemAction.listStore.reload).toHaveBeenCalled();
    expect(itemAction.list.showSuccessSnackbar).toHaveBeenCalled();
});
