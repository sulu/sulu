// @flow
import {observable} from 'mobx';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import List from '../../../../views/List';
import LinkItemAction from '../../itemActions/LinkItemAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/List/stores/ListStore', () => jest.fn(function() {
    this.selectionIds = [];
    this.selections = [];
    this.deletingSelection = false;
}));

jest.mock('../../../../views/List/List', () => jest.fn(function() {
    this.requestSelectionDelete = jest.fn();
}));

jest.mock('../../../../services/Router/Router', () => jest.fn());

function createLinkItemAction(options = {}) {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new LinkItemAction(listStore, list, router, locales, resourceStore, options);
}

test('Return correct icon action config for given item', () => {
    const linkItemAction = createLinkItemAction({link_property: 'url', icon: 'su-eye'});

    const item = {
        url: 'www.sulu.io',
    };

    expect(linkItemAction.getItemActionConfig(item)).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-eye',
    }));
});

test('Return disabled icon action config if no item is given', () => {
    const linkItemAction = createLinkItemAction({link_property: 'url', icon: 'su-eye'});

    expect(linkItemAction.getItemActionConfig()).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-eye',
    }));
});

test('Return disabled icon action config if link_property of given icon is not set', () => {
    const linkItemAction = createLinkItemAction({link_property: 'url', icon: 'su-eye'});

    const item = {
        otherProperty: 'www.sulu.io',
    };

    expect(linkItemAction.getItemActionConfig(item)).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-eye',
    }));
});

test('Open correct link for given item if onClick callback is fired', () => {
    const linkItemAction = createLinkItemAction({link_property: 'url', icon: 'su-eye'});

    const item = {
        url: 'www.sulu.io',
    };
    const itemActionConfig = linkItemAction.getItemActionConfig(item);

    delete window.location;
    window.location = {};

    const clickCallback = itemActionConfig.onClick;
    if (!clickCallback) {
        throw new Error('The onClick callback should not be undefined in this case');
    }

    clickCallback('row-id', 1);

    expect(window.location.href).toEqual('www.sulu.io');
});

test('Throw error if "link_property" option is not set', () => {
    const linkItemAction = createLinkItemAction({});

    expect(() => linkItemAction.getItemActionConfig()).toThrow(/link_property/);
});

test('Throw error if given "icon" option is not a string', () => {
    const linkItemAction = createLinkItemAction({icon: {}, link_property: 'url'});

    expect(() => linkItemAction.getItemActionConfig()).toThrow(/icon/);
});

test('Throw error if given "link_property" option is not a string', () => {
    const linkItemAction = createLinkItemAction({link_property: {}});

    expect(() => linkItemAction.getItemActionConfig()).toThrow(/link_property/);
});

test('Throw error if value of "link_property" of given item is not a string', () => {
    const linkItemAction = createLinkItemAction({link_property: 'url'});

    expect(() => linkItemAction.getItemActionConfig({url: true})).toThrow(/link_property/);
});
