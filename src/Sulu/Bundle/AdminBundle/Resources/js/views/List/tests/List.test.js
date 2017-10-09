/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import TableAdapter from '../../../containers/Datagrid/adapters/TableAdapter';

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function() {
    this.loading = false;
    this.pageCount = 3;
    this.data = [
        {
            id: 1,
            title: 'Title 1',
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            description: 'Description 2',
        },
    ];
    this.selections = [];
    this.getPage = jest.fn().mockReturnValue(2);
    this.getFields = jest.fn().mockReturnValue({
        title: {},
        description: {},
    });
    this.destroy = jest.fn();
    this.sendRequest = jest.fn();
    this.clearSelection = jest.fn();
}));

jest.mock('../../../containers/Datagrid/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../services/ResourceRequester', () => ({
    delete: jest.fn().mockReturnValue(Promise.resolve(true)),
}));

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.delete':
                return 'Delete';
            case 'sulu_admin.add':
                return 'Add';
            case 'sulu_snippet.snippets':
                return 'Snippets';
        }
    },
}));

beforeEach(() => {
    jest.resetModules();

    const datagridAdapterRegistry = require('../../../containers/Datagrid/DatagridAdapterRegistry');
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);
});

test('Should render the datagrid with the correct resourceKey', () => {
    const List = require('../List').default;
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
    };

    const list = render(<List router={router} />);
    expect(list).toMatchSnapshot();
});

test('Should render the list with a title', () => {
    const List = require('../List').default;
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
            },
        },
    };

    const list = render(<List router={router} />);
    expect(list).toMatchSnapshot();
});

test('Should render the datagrid with the pencil icon if a editRoute has been passed', () => {
    const List = require('../List').default;
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                editRoute: 'editRoute',
            },
        },
    };

    const list = render(<List router={router} />);
    expect(list).toMatchSnapshot();
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const List = require('../List').default;
    const router = {
        route: {
            options: {},
        },
    };

    expect(() => render(<List router={router} />)).toThrow(/mandatory resourceKey option/);
});

test('Should unbind the query parameter and destroy the store on unmount', () => {
    const List = require('../List').default;
    const router = {
        bindQuery: jest.fn(),
        unbindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
    };

    const list = mount(<List router={router} />);
    const page = router.bindQuery.mock.calls[0][1];
    const locale = router.bindQuery.mock.calls[1][1];

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bindQuery).toBeCalledWith('page', page, '1');
    expect(router.bindQuery).toBeCalledWith('locale', locale);

    list.unmount();
    expect(router.unbindQuery).toBeCalledWith('page');
    expect(router.unbindQuery).toBeCalledWith('locale');
});

test('Should navigate when pencil button is clicked', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bindQuery: jest.fn(),
        route: {
            options: {
                editRoute: 'editRoute',
                resourceKey: 'test',
            },
        },
    };

    const listWrapper = mount(<List router={router} />);
    listWrapper.find('List').get(0).locale = {
        get: function() {
            return 'de';
        },
    };
    listWrapper.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1}, {locale: 'de'});
});

test('Should render the delete item enabled only if something is selected', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />).get(0);
    const datagridStore = list.datagridStore;

    let toolbarConfig, item;
    toolbarConfig = toolbarFunction.call(list);
    item = toolbarConfig.items.find((item) => item.value === 'Delete');
    expect(item.disabled).toBe(true);

    datagridStore.selections.push(1);
    toolbarConfig = toolbarFunction.call(list);
    item = toolbarConfig.items.find((item) => item.value === 'Delete');
    expect(item.disabled).toBe(false);
});

test('Should render the locale dropdown with the options from router', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                locales: ['en', 'de'],
            },
        },
    };

    const list = mount(<List router={router} />).get(0);
    list.locale = {
        get: function() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(list);
    expect(toolbarConfig.locale.value).toBe('de');
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should delete selected items when click on delete button', () => {
    function getDeleteItem() {
        return toolbarFunction.call(list).items.find((item) => item.value === 'Delete');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const ResourceRequester = require('../../../services/ResourceRequester');
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const router = {
        bindQuery: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />).get(0);
    const datagridStore = list.datagridStore;
    datagridStore.selections = [1, 4, 6];

    expect(getDeleteItem().loading).toBe(false);
    const clickPromise = getDeleteItem().onClick();
    expect(getDeleteItem().loading).toBe(true);

    return clickPromise.then(() => {
        expect(ResourceRequester.delete).toBeCalledWith('test', 1);
        expect(ResourceRequester.delete).toBeCalledWith('test', 4);
        expect(ResourceRequester.delete).toBeCalledWith('test', 6);
        expect(datagridStore.clearSelection).toBeCalled();
        expect(datagridStore.sendRequest).toBeCalled();
        expect(getDeleteItem().loading).toBe(false);
    });
});
