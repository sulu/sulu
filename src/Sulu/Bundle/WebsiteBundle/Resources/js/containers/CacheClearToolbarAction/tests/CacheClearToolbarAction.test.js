// @flow
import {Requester} from 'sulu-admin-bundle/services';
import CacheClearToolbarAction from '../CacheClearToolbarAction';

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    delete: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Return item config with correct icon, type and label and return closed dialog', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    expect(cacheClearToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-paint',
        label: 'sulu_website.cache_clear',
        type: 'button',
    }));

    const dialogNode: any = cacheClearToolbarAction.getNode();
    expect(dialogNode.props).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_website.cache_clear_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_website.cache_clear_warning_title',
    }));
});

test('Open dialog on toolbar item click', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction('sulu-io');

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    const dialogNode: any = cacheClearToolbarAction.getNode();
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog on cancel click', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let dialogNode: any = cacheClearToolbarAction.getNode();
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: true,
    }));

    dialogNode.props.onCancel();
    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when dialog is confirmed', async() => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    let resolveDeletePromise;
    const deletePromise = new Promise((resolve) => {
        resolveDeletePromise = resolve;
    });
    Requester.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let dialogNode: any = cacheClearToolbarAction.getNode();
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: true,
    }));
    expect(dialogNode.props.confirmLoading).toEqual(false);

    dialogNode.props.onConfirm();
    expect(Requester.delete).toBeCalledWith('/cache');

    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.confirmLoading).toEqual(true);

    if (!resolveDeletePromise) {
        throw new Error('Expected delete promise resolver');
    }

    resolveDeletePromise();
    await deletePromise;

    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.confirmLoading).toEqual(false);
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when dialog is confirmed with query parameter', async() => {
    const cacheClearToolbarAction = new CacheClearToolbarAction('sulu-io');
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    let resolveDeletePromise;
    const deletePromise = new Promise((resolve) => {
        resolveDeletePromise = resolve;
    });
    Requester.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let dialogNode: any = cacheClearToolbarAction.getNode();
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: true,
    }));
    expect(dialogNode.props.confirmLoading).toEqual(false);

    dialogNode.props.onConfirm();
    expect(Requester.delete).toBeCalledWith('/cache?webspaceKey=sulu-io');

    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.confirmLoading).toEqual(true);

    if (!resolveDeletePromise) {
        throw new Error('Expected delete promise resolver');
    }

    resolveDeletePromise();
    await deletePromise;

    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.confirmLoading).toEqual(false);
    expect(dialogNode.props).toEqual(expect.objectContaining({
        open: false,
    }));
});
