// @flow
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Requester} from 'sulu-admin-bundle/services';
import CacheClearToolbarAction from '../CacheClearToolbarAction';

let mockDialogProps: Object = {};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/components', () => {
    const actual = jest.requireActual('sulu-admin-bundle/components');

    return {
        ...actual,
        Dialog: jest.fn((props) => {
            mockDialogProps = props;

            return mockReact.createElement(
                'div',
                {
                    'data-open': String(props.open),
                    'data-testid': 'dialog',
                },
                props.open && mockReact.createElement(
                    mockReact.Fragment,
                    {},
                    mockReact.createElement('h1', {}, props.title),
                    mockReact.createElement('div', {}, props.children),
                    mockReact.createElement('button', {onClick: props.onConfirm, type: 'button'}, props.confirmText),
                    mockReact.createElement('button', {onClick: props.onCancel, type: 'button'}, props.cancelText)
                )
            );
        }),
    };
});

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

beforeEach(() => {
    mockDialogProps = {};
});

test('Return item config with correct icon, type and label and return closed dialog', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    expect(cacheClearToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-paint',
        label: 'sulu_website.cache_clear',
        type: 'button',
    }));

    render(cacheClearToolbarAction.getNode());

    expect(mockDialogProps).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_website.cache_clear_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_website.cache_clear_warning_title',
    }));
    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');
});

test('Open dialog on toolbar item click', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction('sulu-io');
    const {rerender} = render(cacheClearToolbarAction.getNode());

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();
    rerender(cacheClearToolbarAction.getNode());

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');
});

test('Close dialog on cancel click', async() => {
    const user = userEvent.setup();
    const cacheClearToolbarAction = new CacheClearToolbarAction();
    const {rerender} = render(cacheClearToolbarAction.getNode());

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();
    rerender(cacheClearToolbarAction.getNode());

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));
    rerender(cacheClearToolbarAction.getNode());

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');
});

test('Call delete when dialog is confirmed', async() => {
    const user = userEvent.setup();
    const cacheClearToolbarAction = new CacheClearToolbarAction();
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    let resolveDelete;
    const deletePromise = new Promise((resolve) => {
        resolveDelete = resolve;
    });
    Requester.delete.mockReturnValue(deletePromise);

    const {rerender} = render(cacheClearToolbarAction.getNode());
    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();
    rerender(cacheClearToolbarAction.getNode());

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');
    expect(mockDialogProps.confirmLoading).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    expect(Requester.delete).toHaveBeenCalledWith('/cache');

    rerender(cacheClearToolbarAction.getNode());
    expect(mockDialogProps.confirmLoading).toEqual(true);

    await act(async() => {
        resolveDelete();
        await deletePromise;
    });
    rerender(cacheClearToolbarAction.getNode());

    expect(mockDialogProps.confirmLoading).toEqual(false);
    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');
});

test('Call delete when dialog is confirmed with query parameter', async() => {
    const user = userEvent.setup();
    const cacheClearToolbarAction = new CacheClearToolbarAction('sulu-io');
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    let resolveDelete;
    const deletePromise = new Promise((resolve) => {
        resolveDelete = resolve;
    });
    Requester.delete.mockReturnValue(deletePromise);

    const {rerender} = render(cacheClearToolbarAction.getNode());
    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();
    rerender(cacheClearToolbarAction.getNode());

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');
    expect(mockDialogProps.confirmLoading).toEqual(false);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));
    expect(Requester.delete).toHaveBeenCalledWith('/cache?webspaceKey=sulu-io');

    rerender(cacheClearToolbarAction.getNode());
    expect(mockDialogProps.confirmLoading).toEqual(true);

    await act(async() => {
        resolveDelete();
        await deletePromise;
    });
    rerender(cacheClearToolbarAction.getNode());

    expect(mockDialogProps.confirmLoading).toEqual(false);
    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');
});
