// @flow
import {Dialog} from 'sulu-admin-bundle/components';
import {Requester} from 'sulu-admin-bundle/services';
import CacheClearToolbarAction from '../CacheClearToolbarAction';

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    delete: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Return item config with correct icon, type and label and return closed dialog', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    expect(cacheClearToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-paint',
        label: 'sulu_website.cache_clear',
        type: 'button',
    }));

    const dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.type).toEqual(Dialog);
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

    const dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.open).toEqual(true);
});

test('Close dialog on cancel click', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.open).toEqual(true);

    (dialogNode: any).props.onCancel();
    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.open).toEqual(false);
});

test('Call delete when dialog is confirmed', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    const deletePromise = Promise.resolve();
    Requester.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.open).toEqual(true);
    expect(dialogNode.props.confirmLoading).toEqual(false);

    dialogNode.props.onConfirm();
    expect(Requester.delete).toBeCalledWith('/cache');

    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.confirmLoading).toEqual(true);

    return deletePromise.then(() => {
        dialogNode = cacheClearToolbarAction.getNode();
        expect(dialogNode.props.confirmLoading).toEqual(false);
        expect(dialogNode.props.open).toEqual(false);
    });
});

test('Call delete when dialog is confirmed with query parameter', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction('sulu-io');
    CacheClearToolbarAction.clearCacheEndpoint = '/cache';

    const deletePromise = Promise.resolve();
    Requester.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.open).toEqual(true);
    expect(dialogNode.props.confirmLoading).toEqual(false);

    dialogNode.props.onConfirm();
    expect(Requester.delete).toBeCalledWith('/cache?webspaceKey=sulu-io');

    dialogNode = cacheClearToolbarAction.getNode();
    expect(dialogNode.props.confirmLoading).toEqual(true);

    return deletePromise.then(() => {
        dialogNode = cacheClearToolbarAction.getNode();
        expect(dialogNode.props.confirmLoading).toEqual(false);
        expect(dialogNode.props.open).toEqual(false);
    });
});
