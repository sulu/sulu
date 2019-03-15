/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import TableAdapter from '../../../containers/List/adapters/TableAdapter';
import listFieldTransformRegistry from '../../../containers/List/registries/ListFieldTransformerRegistry';
import StringFieldTransformer from '../../../containers/List/fieldTransformers/StringFieldTransformer';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';

jest.mock('../../../services/ResourceRequester/registries/ResourceRouteRegistry', () => ({
    getListUrl: jest.fn()
        .mockReturnValue('testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'),
}));

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../containers/List/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../stores/UserStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

jest.mock(
    '../../../containers/List/stores/ListStore',
    () => jest.fn(function(resourceKey, listKey, userSettingsKey, observableOptions, options) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.observableOptions = observableOptions;
        this.options = options;
        this.loading = false;
        this.pageCount = 3;
        this.active = {
            get: jest.fn(),
        };
        this.sortColumn = {
            get: jest.fn(),
        };
        this.sortOrder = {
            get: jest.fn(),
        };
        this.searchTerm = {
            get: jest.fn(),
        };
        this.limit = {
            get: jest.fn().mockReturnValue(10),
        };
        this.setLimit = jest.fn();
        this.updateLoadingStrategy = jest.fn();
        this.updateStructureStrategy = jest.fn();
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
        this.selectionIds = [];
        this.deleteSelection = jest.fn();
        this.getPage = jest.fn().mockReturnValue(2);
        this.userSchema = {
            title: {
                type: 'string',
                sortable: true,
                visibility: 'no',
                label: 'Title',
            },
            description: {
                type: 'string',
                sortable: true,
                visibility: 'yes',
                label: 'Description',
            },
        };
        this.destroy = jest.fn();
        this.reload = jest.fn();
        this.clearSelection = jest.fn();
        this.remove = jest.fn();
        this.moveSelection = jest.fn();

        mockExtendObservable(this, {
            moving: false,
            movingSelection: false,
        });
    })
);

jest.mock('../../../containers/List/registries/ListAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../../../containers/List/registries/ListFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
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
            case 'sulu_admin.move_items':
                return 'Move items';
            case 'sulu_admin.move_selected':
                return 'Move selected';
            case 'sulu_snippet.snippets':
                return 'Snippets';
            case 'sulu_admin.export':
                return 'Export';
        }
    },
}));

jest.mock('../../../services/Initializer', () => ({
    initializedTranslationsLocale: true,
}));

beforeEach(() => {
    jest.resetModules();

    const listAdapterRegistry = require('../../../containers/List/registries/ListAdapterRegistry');
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TableAdapter);

    listFieldTransformRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Should render the list with the correct resourceKey', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
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
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
            },
        },
    };

    const list = render(<List router={router} />);
    expect(list).toMatchSnapshot();
});

test('Should pass correct props to move list overlay', () => {
    const List = require('../List').default;
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
                toolbarActions: ['sulu_admin.move'],
            },
        },
    };

    const list = shallow(<List router={router} />);

    expect(list.find('SingleListOverlay').props()).toEqual(expect.objectContaining({
        listKey: 'snippets_list',
        options: {includeRoot: true},
        reloadOnOpen: true,
        resourceKey: 'snippets',
    }));
});

test('Should pass the onItemClick callback when an editRoute has been passed', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                editRoute: 'editRoute',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemClick')).toBeInstanceOf(Function);
});

test('Should pass the onItemClick callback when an editRoute has been passed', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemClick')).not.toBeInstanceOf(Function);
});

test('Should render the list with the add icon if a addRoute has been passed', () => {
    const List = require('../List').default;
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                addRoute: 'addRoute',
                listKey: 'snippets',
                resourceKey: 'snippets',
                toolbarActions: ['sulu_admin.add'],
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemAdd')).toBeInstanceOf(Function);
});

test('Should render the list without the add icon if a addRoute has been passed', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('onItemAdd')).not.toBeInstanceOf(Function);
});

test('Should render the list non-searchable if the searchable option has been passed as false', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                searchable: false,
            },
        },
    };

    const list = shallow(<List router={router} />);
    expect(list.find('List').prop('searchable')).toEqual(false);
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const List = require('../List').default;
    const router = {
        route: {
            options: {},
        },
    };

    expect(() => render(<List router={router} />)).toThrow(/mandatory "resourceKey" option/);
});

test('Should throw an error when no listKey is defined in the route options', () => {
    const List = require('../List').default;
    const router = {
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
    };

    expect(() => render(<List router={router} />)).toThrow(/mandatory "listKey" option/);
});

test('Should destroy the store on unmount', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                locales: ['de', 'en'],
                resourceKey: 'snippets',
            },
        },
    };

    const list = mount(<List router={router} />);
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    const listStore = list.instance().listStore;

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('page', page, 1);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('active', listStore.active);
    expect(router.bind).toBeCalledWith('sortColumn', listStore.sortColumn);
    expect(router.bind).toBeCalledWith('sortOrder', listStore.sortOrder);
    expect(router.bind).toBeCalledWith('limit', listStore.limit, 10);

    list.unmount();

    expect(listStore.destroy).toBeCalled();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backRoute: 'backRoute',
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(list.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('backRoute', {locale: 'de'});
});

test('Should navigate to defined route on back button click without locale', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backRoute: 'backRoute',
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('backRoute', {});
});

test('Should not render back button when no backRoute is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should render the add button in the toolbar only if an addRoute has been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: ['sulu_admin.add'],
            },
        },
    };

    const list = mount(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.items).toEqual(
        expect.arrayContaining(
            [
                expect.objectContaining({icon: 'su-plus-circle', label: 'Add'}),
            ]
        )
    );
});

test('Should navigate when add button is clicked and locales have been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                locales: ['de', 'en'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: ['sulu_admin.add'],
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };
    const toolbarConfig = toolbarFunction.call(list.instance());

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toBeCalledWith('addRoute', {locale: 'de'});
});

test('Should navigate without locale when pencil button is clicked', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: ['sulu_admin.add'],
            },
        },
    };

    const list = mount(<List router={router} />);
    const toolbarConfig = toolbarFunction.call(list.instance());

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toBeCalledWith('addRoute', {});
});

test('Should navigate when pencil button is clicked and locales have been passed in options', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editRoute: 'editRoute',
                locales: ['de', 'en'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };
    list.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1, locale: 'de'});
});

test('Should navigate without locale when pencil button is clicked', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editRoute: 'editRoute',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1});
});

test('Should load the route attributes from the ListStore', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(50);

    expect(List.getDerivedRouteAttributes({
        options: {
            resourceKey: 'test',
            listKey: 'list_test',
        },
    })).toEqual({
        active: 'some-uuid',
        sortColumn: 'title',
        sortOrder: 'desc',
        limit: 50,
    });
});

test('Should render the delete item enabled only if something is selected', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: ['sulu_admin.delete'],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    let toolbarConfig, item;
    toolbarConfig = toolbarFunction.call(list.instance());
    item = toolbarConfig.items.find((item) => item.label === 'Delete');
    expect(item.disabled).toBe(true);

    listStore.selectionIds.push(1);
    toolbarConfig = toolbarFunction.call(list.instance());
    item = toolbarConfig.items.find((item) => item.label === 'Delete');
    expect(item.disabled).toBe(false);
});

test('Should render the locale dropdown with the options from router', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    list.instance().locale = {
        get: function() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(list.instance());
    expect(toolbarConfig.locale.value).toBe('de');
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should pass apiOptions from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {
                    webspace: 'example',
                },
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.webspace).toEqual('example');
});

test('Should pass router attributes from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        attributes: {
            id: '123-123-123',
            locale: 'en',
            title: 'Sulu is awesome',
        },
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListStore: {'0': 'locale', 1: 'title', 'parentId': 'id'},
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.locale).toEqual('en');
    expect(listStore.options.parentId).toEqual('123-123-123');
    expect(listStore.options.title).toEqual('Sulu is awesome');
});

test('Should pass router attributes array from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        attributes: {
            id: '123-123-123',
            locale: 'en',
            title: 'Sulu is awesome',
        },
        route: {
            options: {
                adapters: ['table'],
                apiOptions: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListStore: observable(['locale', 'title', 'id']),
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.options.locale).toEqual('en');
    expect(listStore.options.id).toEqual('123-123-123');
    expect(listStore.options.title).toEqual('Sulu is awesome');
});

test('Should pass locale and page observables to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).toHaveProperty('locale');
});

test('Should not pass the locale observable to the ListStore if no locales are defined', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).not.toHaveProperty('locale');
});

test('Should delete selected items when delete button is clicked', () => {
    function getDeleteItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Delete');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: ['sulu_admin.delete'],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(false);

    getDeleteItem().onClick();
    list.update();
    expect(list.find('Dialog').at(0).prop('open')).toEqual(true);
});

test('Should make move overlay disappear if cancel is clicked', () => {
    function getMoveItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Move selected');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: ['sulu_admin.move'],
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);

    getMoveItem().onClick();
    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(true);
    list.find('SingleListOverlay[title="Move items"]').prop('onClose')();

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);
});

test('Should move items after move overlay was confirmed', () => {
    function getMoveItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Move selected');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: ['sulu_admin.move'],
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    const moveSelectionPromise = Promise.resolve();
    listStore.moveSelection.mockReturnValue(moveSelectionPromise);

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);

    getMoveItem().onClick();
    listStore.movingSelection = true;
    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(true);
    list.find('SingleListOverlay[title="Move items"]').prop('onConfirm')({id: 5});

    list.update();
    expect(list.find('SingleListOverlay[title="Move items"]').prop('confirmLoading')).toEqual(true);

    expect(listStore.moveSelection).toBeCalledWith(5);

    return moveSelectionPromise.then(() => {
        listStore.movingSelection = false;
        list.update();
        expect(list.find('SingleListOverlay[title="Move items"]').prop('confirmLoading')).toEqual(false);
        expect(list.find('SingleListOverlay[title="Move items"]').prop('open')).toEqual(false);
    });
});

test('Export dialog should open when the button is pressed', () => {
    function getExportItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Export');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: ['sulu_admin.export'],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();
    expect(list.find('Overlay').find({confirmText: 'Export'}).prop('open')).toEqual(false);

    getExportItem().onClick();
    list.update();

    expect(list.find('Overlay').find({confirmText: 'Export'}).prop('open')).toEqual(true);
});

test('Render export dialog', () => {
    function getExportItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Export');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: ['sulu_admin.export'],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);

    list.update();

    getExportItem().onClick();
    list.update();

    expect(list.find('Overlay').find({confirmText: 'Export'}).render()).toMatchSnapshot();
});

test('Export method should be called when the export-button is pressed', () => {
    function getExportItem() {
        return toolbarFunction.call(list.instance()).items.find((item) => item.label === 'Export');
    }

    window.location.assign = jest.fn();

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    toolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const resourceRouteRegistry = require('../../../services/ResourceRequester/registries/ResourceRouteRegistry');
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: ['sulu_admin.export'],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                locales: ['de', 'en'],
            },
        },
    };

    const list = mount(<List router={router} />);
    const listStore = list.instance().listStore;
    listStore.selectionIds.push(1, 4, 6);
    list.update();

    getExportItem().onClick();
    list.update();

    list.find('Overlay').find({confirmText: 'Export'}).find('Button').simulate('click');
    expect(resourceRouteRegistry.getListUrl).toBeCalledWith('test', {
        _format: 'csv',
        locale: list.instance().locale,
        flat: true,
        delimiter: ';',
        escape: '\\',
        enclosure: '"',
        newLine: '\\n',
    });
    expect(window.location.assign).toBeCalledWith(
        'testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'
    );

    expect(list.find('Overlay').find({confirmText: 'Export'}).prop('open')).toEqual(false);
});
