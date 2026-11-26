// @flow
import {observable} from 'mobx';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import Router from 'sulu-admin-bundle/services/Router';
import List from 'sulu-admin-bundle/views/List';
import ShowVersionItemAction from '../ShowVersionItemAction';

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn());

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn());

function createItemAction() {
    const router = new Router({});
    const listStore = new ListStore('media_versions', 'media_versions', 'list', {page: observable.box(1)});
    const list = new List({route: router.route, router});

    return new ShowVersionItemAction(listStore, list, router, undefined, undefined, {});
}

beforeEach(() => {
    window.open = jest.fn();
});

test('Open the admin url of the version inline', () => {
    const itemAction = createItemAction();
    const config = itemAction.getItemActionConfig({
        adminUrl: '/admin/media/1/test.jpg?v=2',
        url: '/media/1/test.jpg?v=2',
        version: 2,
    });

    expect(config).toEqual(expect.objectContaining({icon: 'su-eye', disabled: false}));

    if (!config.onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    config.onClick(2, 0);

    expect(window.open).toHaveBeenCalledWith('/admin/media/1/test.jpg?v=2&inline=1');
});

test('Fall back to the url of the version if it has no admin url', () => {
    const itemAction = createItemAction();
    const config = itemAction.getItemActionConfig({url: '/media/1/test.jpg?v=2', version: 2});

    if (!config.onClick) {
        throw new Error('The onClick callback should not be undefined in this case');
    }
    config.onClick(2, 0);

    expect(window.open).toHaveBeenCalledWith('/media/1/test.jpg?v=2&inline=1');
});

test('Return disabled item action config without callback if no item is given', () => {
    const itemAction = createItemAction();

    expect(itemAction.getItemActionConfig(undefined)).toEqual(expect.objectContaining({
        disabled: true,
        onClick: undefined,
    }));
});
