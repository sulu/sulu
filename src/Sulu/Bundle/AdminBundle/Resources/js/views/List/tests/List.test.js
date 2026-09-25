/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {act, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import TableAdapter from '../../../containers/List/adapters/TableAdapter';
import listFieldTransformRegistry from '../../../containers/List/registries/listFieldTransformerRegistry';
import StringFieldTransformer from '../../../containers/List/fieldTransformers/StringFieldTransformer';
import {
    createDeferred,
    createListStoreMock as mockCreateListStoreMock,
    mockResizeObserver,
} from '../../../utils/TestHelper';
import ResourceStore from '../../../stores/ResourceStore';

let mockListContainer;
let mockListContainerProps;
let mockListStores = [];

mockResizeObserver();

jest.mock('../../../services/ResourceRequester/registries/resourceRouteRegistry', () => ({
    getUrl: jest.fn()
        .mockReturnValue('testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'),
}));

jest.mock('../../../containers/List/List', () => {
    const React = require('react');
    const ListContainer = jest.requireActual('../../../containers/List/List').default;

    const ListContainerMock = React.forwardRef((props, forwardedRef) => {
        mockListContainerProps = props;

        function handleRef(listContainer) {
            mockListContainer = listContainer;

            if (typeof forwardedRef === 'function') {
                forwardedRef(listContainer);
            }
        }

        return React.createElement(ListContainer, {...props, ref: handleRef});
    });
    ListContainerMock.displayName = 'ListContainerMock';

    return ListContainerMock;
});

jest.mock('../../../containers/SingleListOverlay/SingleListOverlay', () => {
    const React = require('react');

    return function SingleListOverlayMock(props) {
        if (!props.open) {
            return null;
        }

        function handleConfirm() {
            props.onConfirm({id: 5});
        }

        return (
            <div
                aria-label={props.title}
                data-list-key={props.listKey}
                data-options={JSON.stringify(props.options)}
                data-reload-on-open={String(props.reloadOnOpen)}
                data-resource-key={props.resourceKey}
                role="dialog"
            >
                <button onClick={props.onClose} type="button">Close</button>
                {React.createElement(
                    'button',
                    {onClick: handleConfirm, type: 'button'},
                    props.confirmLoading ? 'Moving' : 'Confirm move'
                )}
            </div>
        );
    };
});

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
        this.selectionIds = require('mobx').observable([]);
        mockListStores.push(this);
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
            case 'sulu_admin.unexpected_copy_server_error':
                return 'An unexpected error occurred while copying.';
        }
    },
}));

jest.mock('../../../services/initializer', () => ({
    initializedTranslationsLocale: true,
}));

beforeEach(() => {
    jest.resetModules();
    mockListContainer = undefined;
    mockListContainerProps = undefined;
    mockListStores = [];

    const listAdapterRegistry = require('../../../containers/List/registries/listAdapterRegistry');
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TableAdapter);

    listFieldTransformRegistry.get.mockReturnValue(new StringFieldTransformer());
});

function renderListElement(element) {
    const {router} = element.props;
    const Toolbar = require('../../../containers/Toolbar').default;

    if (!router.addUpdateRouteHook) {
        router.addUpdateRouteHook = jest.fn().mockReturnValue(jest.fn());
    }

    render(<Toolbar />);

    return render(element);
}

function getListStore(userSettingsKey = 'list') {
    return mockListStores.find((listStore) => listStore.userSettingsKey === userSettingsKey);
}

function getViewToolbar() {
    return screen.getAllByRole('navigation')[0];
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

    renderListElement(<List router={router} title="Test 1" />);

    expect(getListStore().resourceKey).toEqual('snippets');
    expect(getListStore().listKey).toEqual('snippets');
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

    renderListElement(<List resourceStore={resourceStore} router={router} />);
    const listView = ToolbarActionMock1.mock.calls[0][1];

    expect(ToolbarActionMock1).toHaveBeenCalledWith(
        getListStore(),
        listView,
        router,
        locales,
        resourceStore,
        {'test1': 'value1'}
    );
    expect(ToolbarActionMock2).toHaveBeenCalledWith(
        getListStore(),
        listView,
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

    renderListElement(<List resourceStore={resourceStore} router={router} />);
    const listView = ItemActionMock1.mock.calls[0][1];

    expect(ItemActionMock1).toHaveBeenCalledWith(
        getListStore(),
        listView,
        router,
        locales,
        resourceStore,
        {'test1': 'value1'}
    );
    expect(ItemActionMock2).toHaveBeenCalledWith(
        getListStore(),
        listView,
        router,
        locales,
        resourceStore,
        {'test2': 'value2'}
    );
    expect(ItemActionMock3).not.toHaveBeenCalled();
});

test('Throw error if "toolbarActions" route-option is not an array of objects', () => {
    const List = require('../List').default;
    List.prototype.updateRouteHookDisposer = jest.fn();
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
    List.prototype.updateRouteHookDisposer = jest.fn();
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
        this.destroy = jest.fn();
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

test('Should configure the move list overlay', async() => {
    const user = userEvent.setup();
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

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().selectionIds.push(1);
    });

    await user.click(screen.getByRole('button', {name: /Move selected/}));

    const overlay = screen.getByRole('dialog', {name: 'Move items'});
    expect(overlay).toHaveAttribute('data-list-key', 'snippets_list');
    expect(overlay).toHaveAttribute('data-options', JSON.stringify({includeRoot: true}));
    expect(overlay).toHaveAttribute('data-resource-key', 'snippets');
    expect(overlay).toHaveAttribute('data-reload-on-open', 'true');
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.onItemClick).toBeInstanceOf(Function);
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

    renderListElement(<List onItemClick={jest.fn()} router={router} />);
    expect(mockListContainerProps.onItemClick).toBeInstanceOf(Function);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.onItemClick).not.toBeInstanceOf(Function);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.onItemAdd).toBeInstanceOf(Function);
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

    renderListElement(<List onItemAdd={jest.fn()} router={router} />);
    expect(mockListContainerProps.onItemAdd).toBeInstanceOf(Function);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.onItemAdd).not.toBeInstanceOf(Function);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.searchable).toEqual(false);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.filterable).toEqual(false);
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

    renderListElement(<List router={router} />);
    expect(mockListContainer.props.filterable).toEqual(true);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.showColumnOptions).toEqual(false);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.showColumnOptions).toEqual(true);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.selectable).toEqual(false);
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

    renderListElement(<List router={router} />);
    expect(mockListContainerProps.itemDisabledCondition).toEqual('(_permissions && !_permissions.view)');
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
    const addToolbarActionDestroy = jest.fn();
    const deleteToolbarActionDestroy = jest.fn();

    const AddToolbarAction = jest.fn(function() {
        this.getNode = jest.fn();
        this.getToolbarItemConfig = jest.fn();
        this.setLocales = jest.fn();
        this.destroy = addToolbarActionDestroy;
    });
    const DeleteToolbarAction = jest.fn(function() {
        this.getNode = jest.fn();
        this.getToolbarItemConfig = jest.fn();
        this.setLocales = jest.fn();
        this.destroy = deleteToolbarActionDestroy;
    });

    listToolbarActionRegistry.add('sulu_admin.add', AddToolbarAction);
    listToolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);

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

    const {unmount} = renderListElement(<List router={router} />);
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    const listStore = getListStore();

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toHaveBeenCalledWith('page', page, 1);
    expect(router.bind).toHaveBeenCalledWith('locale', locale);
    expect(router.bind).toHaveBeenCalledWith('active', listStore.active);
    expect(router.bind).toHaveBeenCalledWith('sortColumn', listStore.sortColumn);
    expect(router.bind).toHaveBeenCalledWith('sortOrder', listStore.sortOrder);
    expect(router.bind).toHaveBeenCalledWith('limit', listStore.limit, 10);
    expect(router.bind).toHaveBeenCalledWith('filter', listStore.filterOptions, {});

    unmount();

    expect(listStore.destroy).toHaveBeenCalled();
    expect(addToolbarActionDestroy).toHaveBeenCalledWith();
    expect(deleteToolbarActionDestroy).toHaveBeenCalledWith();
});

test('Should navigate to defined route on back button click', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
    const router = {
        bind: jest.fn(),
        restore: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                backView: 'backView',
                addView: 'addView',
                listKey: 'test',
                locales: ['de', 'en'],
                resourceKey: 'test',
            },
        },
    };

    renderListElement(<List router={router} />);
    const locale = router.bind.mock.calls.find(([key]) => key === 'locale')[1];
    locale.set('de');

    await user.click(within(getViewToolbar()).getByRole('button', {name: 'su-angle-left'}));
    expect(router.restore).toHaveBeenCalledWith('backView', {locale: 'de'});
});

test('Should propagate errors to toolbar', () => {
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);
    const error = 'This is an error';
    act(() => mockListContainerProps.onDeleteError({detail: error}));

    expect(screen.getByRole('button', {name: new RegExp(error)})).toBeInTheDocument();
});

test('Should show the error of a failed copy in the toolbar', async() => {
    const user = userEvent.setup();
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

    renderListElement(<List router={router} />);

    act(() => mockListContainerProps.onCopyError({detail: 'Copying is not allowed'}));

    expect(screen.getByRole('button', {name: /Copying is not allowed/})).toBeInTheDocument();

    await user.click(screen.getByLabelText('su-times'));
    act(() => mockListContainerProps.onCopyError({}));

    expect(screen.getByRole('button', {name: /An unexpected error occurred while copying/})).toBeInTheDocument();
});

test('Should navigate to defined route on back button click without locale', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);

    await user.click(within(getViewToolbar()).getByRole('button', {name: 'su-angle-left'}));
    expect(router.restore).toHaveBeenCalledWith('backView', {});
});

test('Should not render back button when no backView is configured', () => {
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);

    expect(within(getViewToolbar()).queryByRole('button', {name: 'su-angle-left'})).not.toBeInTheDocument();
});

test('Should render the add button in the toolbar when an addView is configured', () => {
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);

    expect(screen.getByRole('button', {name: /Add/})).toBeInTheDocument();
});

test('Should navigate when add button is clicked and locales have been passed in options', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);
    const locale = router.bind.mock.calls.find(([key]) => key === 'locale')[1];
    locale.set('de');
    await user.click(screen.getByRole('button', {name: /Add/}));

    expect(router.navigate).toHaveBeenCalledWith('addView', {locale: 'de'});
});

test('Should navigate without locale when add button is clicked', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);
    await user.click(screen.getByRole('button', {name: /Add/}));

    expect(router.navigate).toHaveBeenCalledWith('addView', {});
});

test('Should fire callback instead of navigate when onItemAdd prop is set and add button is clicked', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
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

    renderListElement(<List onItemAdd={itemAddCallback} router={router} />);
    await user.click(screen.getByRole('button', {name: /Add/}));

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

    renderListElement(<List router={router} />);
    const locale = router.bind.mock.calls.find(([key]) => key === 'locale')[1];
    locale.set('de');
    mockListContainerProps.onItemClick(1);
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

    renderListElement(<List router={router} />);
    mockListContainerProps.onItemClick(1);
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

    renderListElement(<List onItemClick={onItemClickCallback} router={router} />);
    mockListContainerProps.onItemClick(1);

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

    const deleteButton = screen.getByRole('button', {name: /Delete/});
    expect(deleteButton).toBeDisabled();

    act(() => {
        listStore.selectionIds.push(1);
    });
    expect(deleteButton).toBeEnabled();
});

test('Should render the locale dropdown with the options from router', async() => {
    const user = userEvent.setup();
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

    renderListElement(<List router={router} />);
    const locale = router.bind.mock.calls.find(([key]) => key === 'locale')[1];
    act(() => locale.set('de'));

    await user.click(screen.getByRole('button', {name: /^de/}));

    expect(screen.getByRole('button', {name: 'en'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /^su-check de/})).toBeInTheDocument();
});

test('Should render the locale dropdown with the options from props', async() => {
    const user = userEvent.setup();
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

    renderListElement(<List locales={['en', 'de']} router={router} />);
    const locale = router.bind.mock.calls.find(([key]) => key === 'locale')[1];
    act(() => locale.set('de'));

    await user.click(screen.getByRole('button', {name: /^de/}));

    expect(screen.getByRole('button', {name: 'en'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: /^su-check de/})).toBeInTheDocument();
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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List resourceStore={resourceStore} router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();
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

    renderListElement(<List resourceStore={resourceStore} router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

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
    renderListElement(<List locale={locale} router={router} />);
    const listStore = getListStore();

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

    renderListElement(<List router={router} />);
    const listStore = getListStore();

    expect(listStore.observableOptions).toHaveProperty('page');
    expect(listStore.observableOptions).not.toHaveProperty('locale');
});

test('Should fire reload method of ListStore when reload button is clicked', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ReloadToolbarAction = jest.fn(function(listStore, list) {
        this.destroy = jest.fn();
        this.getNode = jest.fn();
        this.getToolbarItemConfig = jest.fn().mockReturnValue({
            label: 'Reload',
            onClick: list.reload,
        });
    });
    listToolbarActionRegistry.add('reload', ReloadToolbarAction);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                listKey: 'test',
                resourceKey: 'test',
                toolbarActions: [
                    {type: 'reload'},
                ],
            },
        },
    };

    renderListElement(<List router={router} />);
    await user.click(screen.getByRole('button', {name: 'Reload'}));

    expect(getListStore().reload).toHaveBeenCalled();
});

test('Should delete selected items when delete button is clicked', async() => {
    const user = userEvent.setup();
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

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().selectionIds.push(1, 4, 6);
    });
    const requestSelectionDelete = jest.spyOn(mockListContainer, 'requestSelectionDelete');

    await user.click(screen.getByRole('button', {name: /Delete/}));

    expect(requestSelectionDelete).toHaveBeenCalledWith(true);
});

test('Should pass allowConflictDeletion correctly to List component', async() => {
    const user = userEvent.setup();
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
                    {type: 'sulu_admin.delete', options: {allow_conflict_deletion: false}},
                ],
            },
        },
    };

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().selectionIds.push(1, 4, 6);
    });
    const requestSelectionDelete = jest.spyOn(mockListContainer, 'requestSelectionDelete');

    await user.click(screen.getByRole('button', {name: /Delete/}));

    expect(requestSelectionDelete).toHaveBeenCalledWith(false);
});

test('Should make move overlay disappear if cancel is clicked', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().selectionIds.push(1, 4, 6);
    });
    expect(screen.queryByRole('dialog', {name: 'Move items'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /Move selected/}));
    expect(screen.getByRole('dialog', {name: 'Move items'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Close'}));

    expect(screen.queryByRole('dialog', {name: 'Move items'})).not.toBeInTheDocument();
});

test('Should move items after move overlay was confirmed', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
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

    renderListElement(<List router={router} />);
    const listStore = getListStore();
    act(() => {
        listStore.selectionIds.push(1, 4, 6);
    });

    const moveSelectionRequest = createDeferred();
    listStore.moveSelection.mockReturnValue(moveSelectionRequest.promise);

    expect(screen.queryByRole('dialog', {name: 'Move items'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /Move selected/}));
    act(() => {
        listStore.movingSelection = true;
    });
    expect(screen.getByRole('dialog', {name: 'Move items'})).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'Moving'}));

    expect(screen.getByRole('button', {name: 'Moving'})).toBeInTheDocument();

    expect(listStore.moveSelection).toHaveBeenCalledWith(5);

    await act(async() => {
        moveSelectionRequest.resolve();
        await moveSelectionRequest.promise;
    });
    act(() => {
        listStore.movingSelection = false;
    });

    expect(screen.queryByRole('dialog', {name: 'Move items'})).not.toBeInTheDocument();
});

test('Export dialog should open when the button is pressed', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
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

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().data.push({id: 1});
    });

    expect(screen.queryByRole('button', {name: 'Export'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /Export/}));

    expect(screen.getByRole('button', {name: 'Export'})).toBeInTheDocument();
});

test('Render export dialog', async() => {
    const user = userEvent.setup();
    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
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

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().data.push({id: 1});
    });

    await user.click(screen.getByRole('button', {name: /Export/}));

    expect(screen.getByRole('button', {name: 'Export'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: '; su-angle-down'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: '" su-angle-down'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: '\\ su-angle-down'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: '\\n su-angle-down'})).toBeInTheDocument();
});

test('Export method should be called when the export-button is pressed', async() => {
    const user = userEvent.setup();
    window.location.assign = jest.fn();

    const List = require('../List').default;
    const listToolbarActionRegistry = require('../registries/listToolbarActionRegistry').default;
    const ExportToolbarAction = require('../toolbarActions/ExportToolbarAction').default;
    listToolbarActionRegistry.add('sulu_admin.export', ExportToolbarAction);
    const resourceRouteRegistry = require('../../../services/ResourceRequester/registries/resourceRouteRegistry');
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

    renderListElement(<List router={router} />);
    act(() => {
        getListStore().data.push({id: 1});
    });

    await user.click(screen.getByRole('button', {name: /Export/}));

    await user.click(screen.getByRole('button', {name: 'Export'}));
    expect(resourceRouteRegistry.getUrl).toHaveBeenCalledWith('list', 'test', {
        _format: 'csv',
        locale: undefined,
        flat: true,
        delimiter: ';',
        escape: '\\',
        enclosure: '"',
        newLine: '\\n',
    });
    expect(window.location.assign).toHaveBeenCalledWith(
        'testfile.csv?locale=en&flat=true&delimiter=%3B&escape=%5C&enclosure=%22&newLine=%5Cn'
    );
});
