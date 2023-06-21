// @flow
import {observable} from 'mobx';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import List from '../../../../views/List';
import ResourceDetailLinkItemAction from '../../itemActions/ResourceDetailLinkItemAction';

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

jest.mock('../../../../services/Router', () => jest.fn(function() {
    this.navigateToResourceView = jest.fn();
    this.hasResourceView = jest.fn();
}));

const router = new Router({});

function createLinkItemAction(options = {}) {
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new ResourceDetailLinkItemAction(listStore, list, router, locales, resourceStore, options);
}

test('Return correct icon action config for given item', () => {
    const linkItemAction = createLinkItemAction({
        icon: 'su-test',
        resource_key_property: 'customResourceKey',
        resource_id_property: 'customResourceId',
        resource_view_attributes_property: 'customResourceViewAttributes',
    });

    router.hasResourceView.mockReturnValue(true);

    const item = {
        customResourceKey: 'resource-key',
        customResourceId: '1',
        customResourceViewAttributes: {},
    };

    const itemActionConfig = linkItemAction.getItemActionConfig(item);

    expect(itemActionConfig).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-test',
    }));

    expect(itemActionConfig.onClick).toBeInstanceOf(Function);
});

test('Return disabled icon action config if no item is given', () => {
    const linkItemAction = createLinkItemAction();

    expect(linkItemAction.getItemActionConfig()).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('Return disabled icon action config if link_property of given icon is not set', () => {
    const linkItemAction = createLinkItemAction();

    router.hasResourceView.mockReturnValue(false);

    const item = {
        resourceKey: 'resource-key',
        resourceId: '1',
        resourceViewAttributes: {},
    };

    const itemActionConfig = linkItemAction.getItemActionConfig(item);

    expect(itemActionConfig).toEqual(expect.objectContaining({
        disabled: true,
    }));
});

test('On click route to correct resource view', () => {
    const linkItemAction = createLinkItemAction();

    router.hasResourceView.mockReturnValue(false);

    const item = {
        resourceKey: 'resource-key',
        resourceId: '1',
        resourceViewAttributes: {},
    };

    const itemActionConfig = linkItemAction.getItemActionConfig(item);

    const clickCallback = itemActionConfig.onClick;
    expect(clickCallback).toBeInstanceOf(Function);

    clickCallback('resource-key', '1', {});
    expect(router.navigateToResourceView).toHaveBeenCalledWith('detail', 'resource-key', {id: '1'});
});

test('Throw error if "resource_key_property" option is not correctly set', () => {
    const linkItemAction = createLinkItemAction({
        resource_key_property: {},
    });

    expect(() => linkItemAction.getItemActionConfig()).toThrow(/resource_key_property/);
});

test('Throw error if "resource_key_property" item is not correctly set', () => {
    const linkItemAction = createLinkItemAction();

    const item = {resourceKey: {}};

    expect(() => linkItemAction.getItemActionConfig(item)).toThrow(/resource_key_property/);
});

test('Throw error if "resource_id_property" option is not correctly set', () => {
    const linkItemAction = createLinkItemAction({
        resource_id_property: {},
    });

    expect(() => linkItemAction.getItemActionConfig()).toThrow(/resource_id_property/);
});

test('Throw error if "resource_key_property" item is not correctly set', () => {
    const linkItemAction = createLinkItemAction();

    const item = {resourceId: {}};

    expect(() => linkItemAction.getItemActionConfig(item)).toThrow(/resource_id_property/);
});

test('Throw error if "resource_view_attributes_property" option is not correctly set', () => {
    const linkItemAction = createLinkItemAction({
        resource_view_attributes_property: {},
    });

    expect(() => linkItemAction.getItemActionConfig()).toThrow(/resource_view_attributes_property/);
});

test('Throw error if "resource_key_property" item is not correctly set', () => {
    const linkItemAction = createLinkItemAction();

    const item = {resourceViewAttributes: 'false'};

    expect(() => linkItemAction.getItemActionConfig(item)).toThrow(/resource_view_attributes_property/);
});
