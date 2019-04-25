// @flow
import {shallow} from 'enzyme';
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

    const element = shallow(cacheClearToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        cancelText: 'sulu_admin.cancel',
        children: 'sulu_website.cache_clear_warning_text',
        confirmText: 'sulu_admin.ok',
        open: false,
        title: 'sulu_website.cache_clear_warning_title',
    }));
});

test('Open dialog on toolbar item click', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    const element = shallow(cacheClearToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));
});

test('Close dialog on cancel click', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let element = shallow(cacheClearToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    element.find('Button[skin="secondary"]').simulate('click');
    element = shallow(cacheClearToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: false,
    }));
});

test('Call delete when dialog is confirmed', () => {
    const cacheClearToolbarAction = new CacheClearToolbarAction();

    const deletePromise = Promise.resolve();
    Requester.delete.mockReturnValue(deletePromise);

    const toolbarItemConfig = cacheClearToolbarAction.getToolbarItemConfig();
    toolbarItemConfig.onClick();

    let element = shallow(cacheClearToolbarAction.getNode());
    expect(element.instance().props).toEqual(expect.objectContaining({
        open: true,
    }));

    expect(element.instance().props.confirmLoading).toEqual(false);
    element.find('Button[skin="primary"]').simulate('click');
    expect(Requester.delete).toBeCalledWith(undefined);

    element = shallow(cacheClearToolbarAction.getNode());
    expect(element.instance().props.confirmLoading).toEqual(true);

    return deletePromise.then(() => {
        element = shallow(cacheClearToolbarAction.getNode());
        expect(element.instance().props.confirmLoading).toEqual(false);
        expect(element.instance().props).toEqual(expect.objectContaining({
            open: false,
        }));
    });
});
