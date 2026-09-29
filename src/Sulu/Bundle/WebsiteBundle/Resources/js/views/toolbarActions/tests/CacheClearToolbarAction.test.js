// @flow
import {act, fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Requester, Router} from 'sulu-admin-bundle/services';
import CacheClearToolbarAction from '../CacheClearToolbarAction';

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function(attributes) {
    this.attributes = attributes || {};
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/views', () => ({
    AbstractViewToolbarAction: require('sulu-admin-bundle/views/toolbarActions/AbstractViewToolbarAction').default,
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function openDialog(cacheClearToolbarAction: CacheClearToolbarAction, rerender: (node: React$Node) => void) {
    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    if (!toolbarItemConfig) {
        throw new Error('The toolbarItemConfig should be a value!');
    }
    toolbarItemConfig.onClick();
    rerender(cacheClearToolbarAction.getNode());
}

function finishDialogCloseTransition() {
    const dialogContainer = document.querySelector('.dialogContainer');

    if (!(dialogContainer instanceof HTMLElement)) {
        throw new Error('Expected dialog container');
    }

    fireEvent.transitionEnd(dialogContainer);
}

test('Return item config with correct icon, type and label and return closed dialog', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction(new Router({}));

    expect(cacheClearToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-paint',
        label: 'sulu_website.cache_clear',
        type: 'button',
    }));

    render(cacheClearToolbarAction.getNode());

    expect(screen.queryByText('sulu_website.cache_clear_warning_title')).not.toBeInTheDocument();
});

test('Open dialog on toolbar item click', () => {
    CacheClearToolbarAction.webspaceCachePermissions = {'sulu-io': true};
    const cacheClearToolbarAction = new CacheClearToolbarAction(new Router({webspace: 'sulu-io'}));
    const {rerender} = render(cacheClearToolbarAction.getNode());

    openDialog(cacheClearToolbarAction, rerender);

    expect(screen.getByText('sulu_website.cache_clear_warning_title')).toBeInTheDocument();
    expect(screen.getByText('sulu_website.cache_clear_warning_text_webspace')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.cancel'})).toBeInTheDocument();
});

test('Close dialog on cancel click', async() => {
    const user = userEvent.setup();
    const cacheClearToolbarAction = new CacheClearToolbarAction(new Router({}));
    const {rerender} = render(cacheClearToolbarAction.getNode());

    openDialog(cacheClearToolbarAction, rerender);

    expect(screen.getByText('sulu_website.cache_clear_warning_title')).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    rerender(cacheClearToolbarAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_website.cache_clear_warning_title')).not.toBeInTheDocument();
});

test('Call delete when dialog is confirmed', async() => {
    const user = userEvent.setup();
    const cacheClearToolbarAction = new CacheClearToolbarAction(new Router({}));
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    let resolveDelete = () => {};
    const deletePromise = new Promise((resolve) => {
        resolveDelete = resolve;
    });
    Requester.delete.mockReturnValue(deletePromise);

    const {rerender} = render(cacheClearToolbarAction.getNode());
    openDialog(cacheClearToolbarAction, rerender);

    expect(screen.getByText('sulu_website.cache_clear_warning_title')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeEnabled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    expect(Requester.delete).toHaveBeenCalledWith('/cache');

    rerender(cacheClearToolbarAction.getNode());
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolveDelete();
        await deletePromise;
    });
    rerender(cacheClearToolbarAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_website.cache_clear_warning_title')).not.toBeInTheDocument();
});

test('Return no item config when user has no cache permission for webspace', () => {
    CacheClearToolbarAction.webspaceCachePermissions = {'sulu-io': false};
    const cacheClearToolbarAction = new CacheClearToolbarAction(new Router({webspace: 'sulu-io'}));

    expect(cacheClearToolbarAction.getToolbarItemConfig()).toEqual(undefined);
});

test('Call delete when dialog is confirmed with query parameter', async() => {
    const user = userEvent.setup();
    CacheClearToolbarAction.webspaceCachePermissions = {'sulu-io': true};
    const cacheClearToolbarAction = new CacheClearToolbarAction(new Router({webspace: 'sulu-io'}));
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    let resolveDelete = () => {};
    const deletePromise = new Promise((resolve) => {
        resolveDelete = resolve;
    });
    Requester.delete.mockReturnValue(deletePromise);

    const {rerender} = render(cacheClearToolbarAction.getNode());
    openDialog(cacheClearToolbarAction, rerender);

    expect(screen.getByText('sulu_website.cache_clear_warning_text_webspace')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeEnabled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    expect(Requester.delete).toHaveBeenCalledWith('/cache?webspaceKey=sulu-io');

    rerender(cacheClearToolbarAction.getNode());
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    await act(async() => {
        resolveDelete();
        await deletePromise;
    });
    rerender(cacheClearToolbarAction.getNode());
    finishDialogCloseTransition();

    expect(screen.queryByText('sulu_website.cache_clear_warning_title')).not.toBeInTheDocument();
});
