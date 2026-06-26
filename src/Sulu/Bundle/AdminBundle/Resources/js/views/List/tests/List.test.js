/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, screen} from '@testing-library/react';
import {observable} from 'mobx';
import TableAdapter from '../../../containers/List/adapters/TableAdapter';
import listFieldTransformRegistry from '../../../containers/List/registries/listFieldTransformerRegistry';
import StringFieldTransformer from '../../../containers/List/fieldTransformers/StringFieldTransformer';
import {
    createListStoreMock as mockCreateListStoreMock,
    findWithHighOrderFunction,
    renderWithRef,
} from '../../../utils/TestHelper';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../../services/ResourceRequester/registries/resourceRouteRegistry', () => ({
    getUrl: jest.fn()
        .mockReturnValue('testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'),
}));

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../containers/List/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../stores/userStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

jest.mock(
    '../../../containers/List/stores/ListStore',
    () => jest.fn(function(resourceKey, listKey, userSettingsKey, observableOptions, options, metadataOptions) {
        mockCreateListStoreMock(
            resourceKey,
            listKey,
            userSettingsKey,
            observableOptions,
            options,
            metadataOptions,
            {},
            this
        );
    })
);

jest.mock(
    '../../../stores/ResourceStore/ResourceStore',
    () => jest.fn(function(resourceKey, id) {
        this.resourceKey = resourceKey;
        this.id = id;
        this.data = {
            id,
            title: 'Sulu rocks',
            locale: 'de',
        };
    })
);

jest.mock('../../../containers/List/registries/listAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../../../containers/List/registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
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

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

beforeEach(() => {
    jest.resetModules();

    const listAdapterRegistry = require('../../../containers/List/registries/listAdapterRegistry');
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TableAdapter);

    listFieldTransformRegistry.get.mockReturnValue(new StringFieldTransformer());
});

function renderListElement(element) {
    const {instance: list, ...utils} = renderWithRef(element, {
        afterRender: (list) => {
            list.toolbarActions.forEach((toolbarAction) => {
                if (!toolbarAction.destroy) {
                    toolbarAction.destroy = jest.fn();
                }
            });
        },
    });

    return {list, ...utils};
}

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

    const {list} = renderListElement(<List router={router} title="Test 1" />);

    expect(list.listStore.resourceKey).toEqual('snippets');
    expect(list.listStore.listKey).toEqual('snippets');
    expect(screen.getByRole('heading', {name: 'Test 1'})).toBeInTheDocument();
    expect(screen.getByText('Description 1')).toBeInTheDocument();
    expect(screen.getByText('Description 2')).toBeInTheDocument();
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

    renderListElement(<List router={router} title="Test 2" />);

    expect(screen.getByRole('heading', {name: 'Snippets'})).toBeInTheDocument();
    expect(screen.getByText('Description 1')).toBeInTheDocument();
    expect(screen.getByText('Description 2')).toBeInTheDocument();
});

test('Should render the list with nodes of given ToolbarActions', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;

    const ToolbarActionMock1 = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn().mockReturnValue(<div key="node-1">toolbar action node</div>);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
    });
    listToolbarActionRegistry.add('mock1', ToolbarActionMock1);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
                toolbarActions: [
                    {
                        type: 'mock1',
                        options: {},
                    },
                ],
            },
        },
    };

    renderListElement(<List router={router} title="Test 2" />);

    expect(screen.getByRole('heading', {name: 'Snippets'})).toBeInTheDocument();
    expect(screen.getByText('toolbar action node')).toBeInTheDocument();
});

test('Should render the list with nodes of given ListItemActions', () => {
    const List = require('../List').default;
    const listItemActionRegistry = require('../registries/listItemActionRegistry').default;

    const ListItemActionMock1 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(<div key="node-1">item action node</div>);
        this.getItemActionConfig = jest.fn().mockReturnValue({icon: 'su-eye'});
    });
    listItemActionRegistry.add('mock1', ListItemActionMock1);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
                itemActions: [
                    {
                        type: 'mock1',
                        options: {},
                    },
                ],
            },
        },
    };

    renderListElement(<List router={router} title="Test 2" />);

    expect(screen.getByRole('heading', {name: 'Snippets'})).toBeInTheDocument();
    expect(screen.getByText('item action node')).toBeInTheDocument();
});

test('Get ToolbarActions from listToolbarActionRegistry and instantiate them correct with the arguments', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const resourceStore = new ResourceStore('tests', '123-456-789');

    const ToolbarActionMock1 = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
        this.setLocales = jest.fn();
    });

    const ToolbarActionMock2 = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
        this.setLocales = jest.fn();
    });

    const ToolbarActionMock3 = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
        this.setLocales = jest.fn();
    });

    listToolbarActionRegistry.add('mock1', ToolbarActionMock1);
    listToolbarActionRegistry.add('mock2', ToolbarActionMock2);

    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                toolbarActions: [
                    {
                        type: 'mock1',
                        options: {'test1': 'value1'},
                    },
                    {
                        type: 'mock2',
                        options: {'test2': 'value2'},
                    },
                ],
            },
        },
    };

    const {list} = renderListElement(<List resourceStore={resourceStore} router={router} />);

    expect(ToolbarActionMock1).toHaveBeenCalledWith(
        list.listStore,
        list,
        router,
        locales,
        resourceStore,
        {'test1': 'value1'}
    );
    expect(ToolbarActionMock2).toHaveBeenCalledWith(
        list.listStore,
        list,
        router,
        locales,
        resourceStore,
        {'test2': 'value2'}
    );
    expect(ToolbarActionMock3).not.toHaveBeenCalled();
});

test('Get ListItemActions from listItemActionRegistry and instantiate them correct with the arguments', () => {
    const List = require('../List').default;
    const listItemActionRegistry = require('../registries/listItemActionRegistry').default;
    const resourceStore = new ResourceStore('tests', '123-456-789');

    const ItemActionMock1 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getItemActionConfig = jest.fn().mockReturnValue({icon: 'su-eye'});
        this.setLocales = jest.fn();
    });

    const ItemActionMock2 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getItemActionConfig = jest.fn().mockReturnValue({icon: 'su-eye'});
        this.setLocales = jest.fn();
    });

    const ItemActionMock3 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getItemActionConfig = jest.fn().mockReturnValue({icon: 'su-eye'});
        this.setLocales = jest.fn();
    });

    listItemActionRegistry.add('mock1', ItemActionMock1);
    listItemActionRegistry.add('mock2', ItemActionMock2);

    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                itemActions: [
                    {
                        type: 'mock1',
                        options: {'test1': 'value1'},
                    },
                    {
                        type: 'mock2',
                        options: {'test2': 'value2'},
                    },
                ],
            },
        },
    };

    const {list} = renderListElement(<List resourceStore={resourceStore} router={router} />);

    expect(ItemActionMock1).toHaveBeenCalledWith(
        list.listStore,
        list,
        router,
        locales,
        resourceStore,
        {'test1': 'value1'}
    );
    expect(ItemActionMock2).toHaveBeenCalledWith(
        list.listStore,
        list,
        router,
        locales,
        resourceStore,
        {'test2': 'value2'}
    );
    expect(ItemActionMock3).not.toHaveBeenCalled();
});

test('Throw error if "toolbarActions" route-option is not an array of objects', () => {
    const List = require('../List').default;
    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                toolbarActions: ['mock1'],
            },
        },
    };

    expect(() => renderListElement(<List router={router} />)).toThrow('but string was given');
});

test('Throw error if "itemActions" route-option is not an array of objects', () => {
    const List = require('../List').default;
    const locales = ['de', 'en'];

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales,
                resourceKey: 'snippets',
                itemActions: ['mock1'],
            },
        },
    };

    expect(() => renderListElement(<List router={router} />)).toThrow('but string was given');
});

test('Update locales of given ToolbarActions if "locales" prop is changed', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;

    const setLocalesSpy = jest.fn();
    const ToolbarActionMock1 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getToolbarItemConfig = jest.fn().mockReturnValue({});
        this.setLocales = setLocalesSpy;
    });

    listToolbarActionRegistry.add('mock1', ToolbarActionMock1);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales: ['de', 'en'],
                resourceKey: 'snippets',
                toolbarActions: [
                    {
                        type: 'mock1',
                        options: {},
                    },
                ],
            },
        },
    };

    const {rerender} = renderListElement(<List router={router} />);

    setLocalesSpy.mockClear();
    expect(setLocalesSpy).not.toHaveBeenCalled();
    rerender(
        <List
            router={{
                ...router,
                route: {
                    ...router.route,
                    options: {
                        ...router.route.options,
                        locales: ['de', 'ru'],
                    },
                },
            }}
        />
    );
    expect(setLocalesSpy).toHaveBeenCalledWith(['de', 'ru']);
});

test('Update locales of given ListItemActions if "locales" prop is changed', () => {
    const List = require('../List').default;
    const listItemActionRegistry = require('../registries/listItemActionRegistry').default;

    const setLocalesSpy = jest.fn();
    const ListItemActionMock1 = jest.fn(function() {
        this.getNode = jest.fn().mockReturnValue(null);
        this.getItemActionConfig = jest.fn().mockReturnValue({icon: 'su-eye'});
        this.setLocales = setLocalesSpy;
    });

    listItemActionRegistry.add('mock1', ListItemActionMock1);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                locales: ['de', 'en'],
                resourceKey: 'snippets',
                itemActions: [
                    {
                        type: 'mock1',
                        options: {},
                    },
                ],
            },
        },
    };

    const {rerender} = renderListElement(<List router={router} />);

    setLocalesSpy.mockClear();
    expect(setLocalesSpy).not.toHaveBeenCalled();
    rerender(
        <List
            router={{
                ...router,
                route: {
                    ...router.route,
                    options: {
                        ...router.route.options,
                        locales: ['de', 'ru'],
                    },
                },
            }}
        />
    );
    expect(setLocalesSpy).toHaveBeenCalledWith(['de', 'ru']);
});

test('Should pass correct props to move list overlay', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets_list',
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
                toolbarActions: [
                    {type: 'sulu_admin.move', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);

    expect(list.toolbarActions[0].getNode().props).toEqual(expect.objectContaining({
        listKey: 'snippets_list',
        options: {includeRoot: true},
        reloadOnOpen: true,
        resourceKey: 'snippets',
    }));
});

test('Should pass the onItemClick callback when an editView has been passed', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                editView: 'editView',
                resourceKey: 'snippets',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.onItemClick).toBeInstanceOf(Function);
});

test('Should pass the onItemClick callback if onItemClick prop is set', () => {
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

    const {list} = renderListElement(<List onItemClick={jest.fn()} router={router} />);
    expect(list.list.props.onItemClick).toBeInstanceOf(Function);
});

test('Should not pass the onItemClick callback if no editView has been passed and no onItemClick prop is set', () => {
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

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.onItemClick).not.toBeInstanceOf(Function);
});

test('Should render the list with the add icon if a addView has been passed', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                addView: 'addView',
                listKey: 'snippets',
                resourceKey: 'snippets',
                toolbarActions: [
                    {type: 'sulu_admin.add', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.onItemAdd).toBeInstanceOf(Function);
});

test('Should render the list with the add icon if onItemAdd prop is set', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                toolbarActions: [
                    {type: 'sulu_admin.add', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List onItemAdd={jest.fn()} router={router} />);
    expect(list.list.props.onItemAdd).toBeInstanceOf(Function);
});

test('Should render the list without add icon if no addView has been passed and onItemAdd prop is not set', () => {
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

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.onItemAdd).not.toBeInstanceOf(Function);
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

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.searchable).toEqual(false);
});

test('Should render the list non-filterable if the filterable option has been passed as false', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                filterable: false,
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.filterable).toEqual(false);
});

test('Should render the list filterable if the filterable option has not been passed', () => {
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

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.filterable).toEqual(true);
});

test('Should render the list without columnOptions if the hideColumnOptions option has been passed as true', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                hideColumnOptions: true,
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.showColumnOptions).toEqual(false);
});

test('Should render the list with columnOptions if the hideColumnOptions option has not been passed', () => {
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

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.showColumnOptions).toEqual(true);
});

test('Should render the list non-selectable if the selectable option has been passed as false', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                selectable: false,
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.selectable).toEqual(false);
});

test('Should render the list with the passed itemDisabledCondition option', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['tree_table'],
                listKey: 'snippets',
                resourceKey: 'snippets',
                itemDisabledCondition: '(_permissions && !_permissions.view)',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    expect(list.list.props.itemDisabledCondition).toEqual('(_permissions && !_permissions.view)');
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const List = require('../List').default;
    const router = {
        route: {
            options: {},
        },
    };

    expect(() => renderListElement(<List router={router} />)).toThrow(/mandatory "resourceKey" option/);
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

    expect(() => renderListElement(<List router={router} />)).toThrow(/mandatory "listKey" option/);
});

test('Should destroy the store on unmount', () => {
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;

    listToolbarActionRegistry.add('sulu_admin.add', jest.fn(function() {
        this.getNode = jest.fn();
        this.setLocales = jest.fn();
        this.destroy = jest.fn();
    }));

    listToolbarActionRegistry.add('sulu_admin.delete', jest.fn(function() {
        this.getNode = jest.fn();
        this.setLocales = jest.fn();
        this.destroy = jest.fn();
    }));

    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'snippets',
                locales: ['de', 'en'],
                resourceKey: 'snippets',
                toolbarActions: [
                    {
                        type: 'sulu_admin.add',
                    },
                    {
                        type: 'sulu_admin.delete',
                    },
                ],
            },
        },
    };

    const {list, unmount} = renderListElement(<List router={router} />);
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    const listStore = list.listStore;

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toHaveBeenCalledWith('page', page, 1);
    expect(router.bind).toHaveBeenCalledWith('locale', locale);
    expect(router.bind).toHaveBeenCalledWith('active', listStore.active);
    expect(router.bind).toHaveBeenCalledWith('sortColumn', listStore.sortColumn);
    expect(router.bind).toHaveBeenCalledWith('sortOrder', listStore.sortOrder);
    expect(router.bind).toHaveBeenCalledWith('limit', listStore.limit, 10);
    expect(router.bind).toHaveBeenCalledWith('filter', listStore.filterOptions, {});

    const toolbarActions = list.toolbarActions;

    unmount();

    expect(listStore.destroy).toHaveBeenCalled();
    expect(toolbarActions[0].destroy).toHaveBeenCalledWith();
    expect(toolbarActions[1].destroy).toHaveBeenCalledWith();
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
                backView: 'backView',
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    list.locale = {
        get() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(list);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('backView', {locale: 'de'});
});

test('Should propagate errors to toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backView: 'backView',
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const error = 'This is an error';
    list.errors.push(error);

    const toolbarConfig = toolbarFunction.call(list);
    expect(toolbarConfig.errors.length).toBe(1);
    expect(toolbarConfig.errors[0]).toBe(error);
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
                backView: 'backView',
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('backView', {});
});

test('Should not render back button when no backView is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list);
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should render the add button in the toolbar only if an addView has been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.add', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);

    const toolbarConfig = toolbarFunction.call(list);
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
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addView: 'addView',
                locales: ['de', 'en'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.add', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    list.locale = {
        get() {
            return 'de';
        },
    };
    const toolbarConfig = toolbarFunction.call(list);

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toHaveBeenCalledWith('addView', {locale: 'de'});
});

test('Should navigate without locale when add button is clicked', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.add', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const toolbarConfig = toolbarFunction.call(list);

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toHaveBeenCalledWith('addView', {});
});

test('Should fire callback instead of navigate when onItemAdd prop is set and add button is clicked', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const AddToolbarAction = require('../toolbarActions/AddToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addView: 'addView',
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.add', options: {}},
                ],
            },
        },
    };
    const itemAddCallback = jest.fn();

    const {list} = renderListElement(<List onItemAdd={itemAddCallback} router={router} />);
    const toolbarConfig = toolbarFunction.call(list);

    toolbarConfig.items[0].onClick();

    expect(itemAddCallback).toHaveBeenCalledWith(undefined);
    expect(router.navigate).not.toHaveBeenCalled();
});

test('Should navigate when pencil button is clicked and locales have been passed in options', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editView: 'editView',
                locales: ['de', 'en'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    list.locale = {
        get() {
            return 'de';
        },
    };
    list.list.props.onItemClick(1);
    expect(router.navigate).toHaveBeenCalledWith('editView', {id: 1, locale: 'de'});
});

test('Should navigate without locale when pencil button is clicked', () => {
    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editView: 'editView',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    list.list.props.onItemClick(1);
    expect(router.navigate).toHaveBeenCalledWith('editView', {id: 1});
});

test('Should fire callback instead of navigate when onItemClick prop is set and pencil button is clicked', () => {
    const onItemClickCallback = jest.fn();

    const List = require('../List').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                editView: 'editView',
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List onItemClick={onItemClickCallback} router={router} />);
    list.list.props.onItemClick(1);

    expect(onItemClickCallback).toHaveBeenCalledWith(1);
    expect(router.navigate).not.toHaveBeenCalled();
});

test('Should load the route attributes from the ListStore', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getFilterSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getFilterSetting.mockReturnValueOnce({test: {eq: 'Test'}});
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(50);

    expect(List.getDerivedRouteAttributes({
        options: {
            listKey: 'list_test',
            resourceKey: 'test',
        },
    })).toEqual({
        active: 'some-uuid',
        filter: {
            test: {
                eq: 'Test',
            },
        },
        limit: 50,
        sortColumn: 'title',
        sortOrder: 'desc',
    });

    expect(ListStore.getActiveSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getSortColumnSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getFilterSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getSortOrderSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getLimitSetting).toHaveBeenCalledWith('list_test', 'list');
});

test('Should return the limit route attributes as undefined if ListStore is set to default value', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getFilterSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(10);

    expect(List.getDerivedRouteAttributes({
        options: {
            listKey: 'list_test',
            resourceKey: 'test',
        },
    })).toEqual({
        active: 'some-uuid',
        limit: undefined,
        sortColumn: 'title',
        sortOrder: 'desc',
    });

    expect(ListStore.getActiveSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getSortColumnSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getFilterSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getSortOrderSetting).toHaveBeenCalledWith('list_test', 'list');
    expect(ListStore.getLimitSetting).toHaveBeenCalledWith('list_test', 'list');
});

test('Should load the route attributes from the ListStore using the passed userSettingsKey', () => {
    const List = require('../List').default;
    const ListStore = require('../../../containers/List').ListStore;
    ListStore.getActiveSetting = jest.fn();
    ListStore.getSortColumnSetting = jest.fn();
    ListStore.getFilterSetting = jest.fn();
    ListStore.getSortOrderSetting = jest.fn();
    ListStore.getLimitSetting = jest.fn();

    ListStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    ListStore.getSortColumnSetting.mockReturnValueOnce('title');
    ListStore.getSortOrderSetting.mockReturnValueOnce('desc');
    ListStore.getLimitSetting.mockReturnValueOnce(50);

    expect(List.getDerivedRouteAttributes({
        options: {
            listKey: 'list_test',
            resourceKey: 'test',
            userSettingsKey: 'user_key',
        },
    })).toEqual({
        active: 'some-uuid',
        limit: 50,
        sortColumn: 'title',
        sortOrder: 'desc',
    });

    expect(ListStore.getActiveSetting).toHaveBeenCalledWith('list_test', 'user_key');
    expect(ListStore.getSortColumnSetting).toHaveBeenCalledWith('list_test', 'user_key');
    expect(ListStore.getFilterSetting).toHaveBeenCalledWith('list_test', 'user_key');
    expect(ListStore.getSortOrderSetting).toHaveBeenCalledWith('list_test', 'user_key');
    expect(ListStore.getLimitSetting).toHaveBeenCalledWith('list_test', 'user_key');
});

test('Should render the delete item enabled only if something is selected', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.delete', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

    let toolbarConfig, item;
    toolbarConfig = toolbarFunction.call(list);
    item = toolbarConfig.items.find((item) => item.label === 'Delete');
    expect(item.disabled).toBe(true);

    listStore.selectionIds.push(1);
    toolbarConfig = toolbarFunction.call(list);
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

    const {list} = renderListElement(<List router={router} />);
    list.locale = {
        get() {
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

test('Should render the locale dropdown with the options from props', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
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

    const {list} = renderListElement(<List locales={['en', 'de']} router={router} />);
    list.locale = {
        get() {
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

test('Should pass requestParameters from router to the ListStore', () => {
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                requestParameters: {
                    webspace: 'example',
                },
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

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
                requestParameters: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListRequest: {'0': 'locale', 1: 'title', 'id': 'parentId'},
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

    expect(listStore.options.locale).toEqual('en');
    expect(listStore.options.parentId).toEqual('123-123-123');
    expect(listStore.options.title).toEqual('Sulu is awesome');
});

test('Should pass resourceStore properties from router to the ListStore', () => {
    const List = require('../List').default;
    const resourceStore = new ResourceStore('tests', '123-456-789');
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                requestParameters: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                resourceStorePropertiesToListRequest: {'0': 'locale', 1: 'title', 'id': 'parentId'},
            },
        },
    };

    const {list} = renderListElement(<List resourceStore={resourceStore} router={router} />);
    const listStore = list.listStore;

    expect(listStore.options.locale).toEqual('de');
    expect(listStore.options.parentId).toEqual('123-456-789');
    expect(listStore.options.title).toEqual('Sulu rocks');
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
                requestParameters: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListRequest: observable(['locale', 'title', 'id']),
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

    expect(listStore.options.locale).toEqual('en');
    expect(listStore.options.id).toEqual('123-123-123');
    expect(listStore.options.title).toEqual('Sulu is awesome');
});

test('Should pass router attributes array from router to the ListStore metadataOptions', () => {
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
                requestParameters: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                routerAttributesToListMetadata: ['locale', 'id'],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

    expect(listStore.metadataOptions.locale).toEqual('en');
    expect(listStore.metadataOptions.id).toEqual('123-123-123');
    expect(listStore.metadataOptions.title).toBeUndefined();
});

test('Should pass metadataRequestParameters to metadataOptions', () => {
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
                requestParameters: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                metadataRequestParameters: {
                    showResource: true,
                },
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    expect(listStore.metadataOptions.showResource).toEqual(true);
});

test('Should pass resource-store properties array from router to the ListStore metadataOptions', () => {
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
                requestParameters: {},
                listKey: 'test',
                locales: ['en', 'de'],
                resourceKey: 'test',
                resourceStorePropertiesToListMetadata: {0: 'locale', 'id': 'pageId'},
            },
        },
    };
    const resourceStore = new ResourceStore('tests', '123-123-123');

    const {list} = renderListElement(<List resourceStore={resourceStore} router={router} />);
    const listStore = list.listStore;

    expect(listStore.metadataOptions.locale).toEqual('de');
    expect(listStore.metadataOptions.pageId).toEqual('123-123-123');
    expect(listStore.metadataOptions.title).toBeUndefined();
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

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).toHaveProperty('locale');
});

test('Should pass locale observable from props to the ListStore if it is set', () => {
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

    const locale = observable.box('ru');
    const {list} = renderListElement(<List locale={locale} router={router} />);
    const listStore = list.listStore;

    expect(listStore.observableOptions.locale).toEqual(locale);
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

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).not.toHaveProperty('locale');
});

test('Should fire reload method of ListStore when reload method is called', () => {
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

    const {list} = renderListElement(<List router={router} />);
    list.reload();

    expect(list.listStore.reload).toHaveBeenCalled();
});

test('Should delete selected items when delete button is clicked', () => {
    function getDeleteItem() {
        return toolbarFunction.call(list).items.find((item) => item.label === 'Delete');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.delete', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);

    expect(list.list.showDeleteSelectionDialog).toEqual(false);

    act(() => getDeleteItem().onClick());

    expect(list.list.showDeleteSelectionDialog).toEqual(true);
});

test('Should pass allowConflictDeletion correctly to List component', () => {
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const DeleteToolbarAction = require('../toolbarActions/DeleteToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.delete', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);
    act(() => list.requestSelectionDelete(false));

    expect(list.list.showDeleteSelectionDialog).toEqual(true);

    expect(list.list.allowConflictDeletion).toEqual(false);
});

test('Should make move overlay disappear if cancel is clicked', () => {
    function getMoveItem() {
        return toolbarFunction.call(list).items.find((item) => item.label === 'Move selected');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.move', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);
    const moveToolbarAction = list.toolbarActions[0];

    expect(moveToolbarAction.showOverlay).toEqual(false);

    act(() => getMoveItem().onClick());
    expect(moveToolbarAction.showOverlay).toEqual(true);

    act(() => moveToolbarAction.handleClose());

    expect(moveToolbarAction.showOverlay).toEqual(false);
});

test('Should move items after move overlay was confirmed', () => {
    function getMoveItem() {
        return toolbarFunction.call(list).items.find((item) => item.label === 'Move selected');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const MoveToolbarAction = require('../toolbarActions/MoveToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.move', MoveToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'sulu_admin.move', options: {}},
                ],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);
    const moveToolbarAction = list.toolbarActions[0];

    const moveSelectionPromise = Promise.resolve();
    listStore.moveSelection.mockReturnValue(moveSelectionPromise);

    expect(moveToolbarAction.showOverlay).toEqual(false);

    act(() => getMoveItem().onClick());
    act(() => {
        listStore.movingSelection = true;
    });
    expect(moveToolbarAction.showOverlay).toEqual(true);
    act(() => moveToolbarAction.handleConfirm({id: 5}));

    expect(moveToolbarAction.getNode().props.confirmLoading).toEqual(true);

    expect(listStore.moveSelection).toHaveBeenCalledWith(5);

    return moveSelectionPromise.then(() => {
        act(() => {
            listStore.movingSelection = false;
        });

        expect(moveToolbarAction.getNode().props.confirmLoading).toEqual(false);
        expect(moveToolbarAction.showOverlay).toEqual(false);
    });
});

test('Export dialog should open when the button is pressed', () => {
    function getExportItem() {
        return toolbarFunction.call(list).items.find((item) => item.label === 'Export');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: [
                    {type: 'sulu_admin.export', options: {}},
                ],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);
    const exportToolbarAction = list.toolbarActions[0];

    expect(exportToolbarAction.showOverlay).toEqual(false);

    act(() => getExportItem().onClick());

    expect(exportToolbarAction.showOverlay).toEqual(true);
});

test('Render export dialog', () => {
    function getExportItem() {
        return toolbarFunction.call(list).items.find((item) => item.label === 'Export');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: [
                    {type: 'sulu_admin.export', options: {}},
                ],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);
    const exportToolbarAction = list.toolbarActions[0];

    act(() => getExportItem().onClick());

    const exportOverlay = exportToolbarAction.getNode();

    expect(exportOverlay.props.confirmText).toEqual('Export');
    expect(exportOverlay.props.open).toEqual(true);
    expect(exportToolbarAction.delimiter).toEqual(';');
    expect(exportToolbarAction.enclosure).toEqual('"');
    expect(exportToolbarAction.escape).toEqual('\\');
    expect(exportToolbarAction.newLine).toEqual('\\n');
});

test('Export method should be called when the export-button is pressed', () => {
    function getExportItem() {
        return toolbarFunction.call(list).items.find((item) => item.label === 'Export');
    }

    window.location.assign = jest.fn();

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const resourceRouteRegistry = require('../../../services/ResourceRequester/registries/resourceRouteRegistry');
    const toolbarFunction = findWithHighOrderFunction(withToolbar, List);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                toolbarActions: [
                    {type: 'sulu_admin.export', options: {}},
                ],
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                locales: ['de', 'en'],
            },
        },
    };

    const {list} = renderListElement(<List router={router} />);
    const listStore = list.listStore;
    listStore.selectionIds.push(1, 4, 6);
    const exportToolbarAction = list.toolbarActions[0];

    act(() => getExportItem().onClick());

    act(() => exportToolbarAction.handleConfirm());
    expect(resourceRouteRegistry.getUrl).toHaveBeenCalledWith('list', 'test', {
        _format: 'csv',
        locale: list.locale.get(),
        flat: true,
        delimiter: ';',
        escape: '\\',
        enclosure: '"',
        newLine: '\\n',
    });
    expect(window.location.assign).toHaveBeenCalledWith(
        'testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'
    );
    expect(exportToolbarAction.showOverlay).toEqual(false);
});
