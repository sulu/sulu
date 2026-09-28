// @flow
import {act, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import Router from 'sulu-admin-bundle/services/Router';
import List from 'sulu-admin-bundle/views/List';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import RestoreVersionItemAction from '../../itemActions/RestoreVersionItemAction';

jest.mock('sulu-admin-bundle/utils/Translator');

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

function openDialog(itemAction: RestoreVersionItemAction, rerender: (node: React$Node) => void) {
    const onClick = itemAction.getItemActionConfig({id: 'version-id-1234'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }

    onClick('version-id-1234', 1);
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
    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});

    expect(itemAction.getItemActionConfig({id: 'version-id-1234'})).toEqual(expect.objectContaining({
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
    const {rerender} = render(itemAction.getNode());

    expect(screen.queryByText('sulu_page.restore_version')).not.toBeInTheDocument();

    openDialog(itemAction, rerender);

    expect(screen.getByText('sulu_page.restore_version')).toBeInTheDocument();
    expect(screen.getByText('sulu_page.restore_version_text')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
});

test('Close dialog if it is canceled', async() => {
    const user = userEvent.setup();
    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});
    const {rerender} = render(itemAction.getNode());

    openDialog(itemAction, rerender);

    expect(screen.getByText('sulu_page.restore_version')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_page.restore_version')).not.toBeInTheDocument();
});

test('Send request and navigate to "success_view" if dialog is confirmed', async() => {
    const user = userEvent.setup();
    let resolvePost = () => {};
    const postPromise = new Promise((resolve) => {
        resolvePost = resolve;
    });
    ResourceRequester.post.mockReturnValue(postPromise);

    const itemAction = createItemAction({success_view: 'sulu_page.page_edit_form'});
    itemAction.router.attributes = {id: 'page-id', locale: 'de', webspace: 'sulu'};
    const {rerender} = render(itemAction.getNode());

    openDialog(itemAction, rerender);
    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'list-resource-key',
        {},
        {action: 'restore', version: 'version-id-1234', id: 'page-id', locale: 'de', webspace: 'sulu'}
    );
    rerender(itemAction.getNode());
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolvePost();
        await postPromise;
    });
    rerender(itemAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_page.restore_version')).not.toBeInTheDocument();
    expect(itemAction.router.navigate).toHaveBeenCalledWith(
        'sulu_page.page_edit_form',
        {id: 'page-id', locale: 'de', webspace: 'sulu'}
    );
});

test('Throw error when dialog is confirmed if given "success_view" option is not a string', () => {
    const itemAction = createItemAction({});
    const onClick = itemAction.getItemActionConfig({id: 'version-id-1234'}).onClick;
    if (!onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    onClick('version-id-1234', 1);

    expect(() => itemAction.handleDialogConfirm()).toThrow(/success_view/);
});
