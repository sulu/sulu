/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import TableAdapter from '../../../containers/Datagrid/adapters/TableAdapter';
import datagridFieldTransformRegistry from '../../../containers/Datagrid/registries/DatagridFieldTransformerRegistry';
import StringFieldTransformer from '../../../containers/Datagrid/fieldTransformers/StringFieldTransformer';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../containers/Datagrid/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../stores/UserStore', () => ({
    setPersistentSetting: jest.fn(),
    getPersistentSetting: jest.fn(),
}));

jest.mock(
    '../../../containers/Datagrid/stores/DatagridStore',
    () => jest.fn(function(resourceKey, userSettingsKey, observableOptions, options) {
        this.resourceKey = resourceKey;
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
    })
);

jest.mock('../../../containers/Datagrid/registries/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    getOptions: jest.fn().mockReturnValue({}),
    has: jest.fn(),
}));

jest.mock('../../../containers/Datagrid/registries/DatagridFieldTransformerRegistry', () => ({
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
            case 'sulu_snippet.snippets':
                return 'Snippets';
        }
    },
}));

jest.mock('../../../services/Initializer', () => ({
    initializedTranslationsLocale: true,
}));

beforeEach(() => {
    jest.resetModules();

    const datagridAdapterRegistry = require('../../../containers/Datagrid/registries/DatagridAdapterRegistry');
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.get.mockReturnValue(TableAdapter);

    datagridFieldTransformRegistry.get.mockReturnValue(new StringFieldTransformer());
});

test('Should render the datagrid with the correct resourceKey', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                adapters: ['table'],
            },
        },
    };

    const datagrid = render(<Datagrid router={router} />);
    expect(datagrid).toMatchSnapshot();
});

test('Should render the datagrid with a title', () => {
    const Datagrid = require('../Datagrid').default;

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                title: 'sulu_snippet.snippets',
                adapters: ['table'],
            },
        },
    };

    const datagrid = render(<Datagrid router={router} />);
    expect(datagrid).toMatchSnapshot();
});

test('Should pass the onItemClick callback when an editRoute has been passed', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                editRoute: 'editRoute',
                adapters: ['table'],
            },
        },
    };

    const datagrid = shallow(<Datagrid router={router} />);
    expect(datagrid.find('Datagrid').prop('onItemClick')).toBeInstanceOf(Function);
});

test('Should pass the onItemClick callback when an editRoute has been passed', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                adapters: ['table'],
            },
        },
    };

    const datagrid = shallow(<Datagrid router={router} />);
    expect(datagrid.find('Datagrid').prop('onItemClick')).not.toBeInstanceOf(Function);
});

test('Should render the datagrid with the add icon if a addRoute has been passed', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                addRoute: 'addRoute',
                adapters: ['tree_table'],
            },
        },
    };

    const datagrid = shallow(<Datagrid router={router} />);
    expect(datagrid.find('Datagrid').prop('onItemAdd')).toBeInstanceOf(Function);
});

test('Should render the datagrid without the add icon if a addRoute has been passed', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                adapters: ['tree_table'],
            },
        },
    };

    const datagrid = shallow(<Datagrid router={router} />);
    expect(datagrid.find('Datagrid').prop('onItemAdd')).not.toBeInstanceOf(Function);
});

test('Should render the datagrid non-searchable if the searchable option has been passed as false', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                adapters: ['tree_table'],
                searchable: false,
            },
        },
    };

    const datagrid = shallow(<Datagrid router={router} />);
    expect(datagrid.find('Datagrid').prop('searchable')).toEqual(false);
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        route: {
            options: {},
        },
    };

    expect(() => render(<Datagrid router={router} />)).toThrow(/mandatory resourceKey option/);
});

test('Should destroy the store on unmount', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                locales: ['de', 'en'],
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    const datagridStore = datagrid.instance().datagridStore;

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('page', page, 1);
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('active', datagridStore.active);
    expect(router.bind).toBeCalledWith('sortColumn', datagridStore.sortColumn);
    expect(router.bind).toBeCalledWith('sortOrder', datagridStore.sortOrder);
    expect(router.bind).toBeCalledWith('limit', datagridStore.limit, 10);

    datagrid.unmount();

    expect(datagridStore.destroy).toBeCalled();
});

test('Should render the add button in the toolbar only if an addRoute has been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                resourceKey: 'test',
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);

    const toolbarConfig = toolbarFunction.call(datagrid.instance());
    expect(toolbarConfig.items).toEqual(
        expect.arrayContaining(
            [
                expect.objectContaining({icon: 'su-plus-circle', value: 'Add'}),
            ]
        )
    );
});

test('Should navigate when add button is clicked and locales have been passed in options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                addRoute: 'addRoute',
                resourceKey: 'test',
                locales: ['de', 'en'],
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    datagrid.instance().locale = {
        get: function() {
            return 'de';
        },
    };
    const toolbarConfig = toolbarFunction.call(datagrid.instance());

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toBeCalledWith('addRoute', {locale: 'de'});
});

test('Should navigate without locale when pencil button is clicked', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                addRoute: 'addRoute',
                resourceKey: 'test',
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const toolbarConfig = toolbarFunction.call(datagrid.instance());

    toolbarConfig.items[0].onClick();

    expect(router.navigate).toBeCalledWith('addRoute', {});
});

test('Should navigate when pencil button is clicked and locales have been passed in options', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                editRoute: 'editRoute',
                resourceKey: 'test',
                locales: ['de', 'en'],
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    datagrid.instance().locale = {
        get: function() {
            return 'de';
        },
    };
    datagrid.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1, locale: 'de'});
});

test('Should navigate without locale when pencil button is clicked', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                editRoute: 'editRoute',
                resourceKey: 'test',
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    datagrid.find('ButtonCell button').at(0).simulate('click');
    expect(router.navigate).toBeCalledWith('editRoute', {id: 1});
});

test('Should load the route attributes from the DatagridStore', () => {
    const Datagrid = require('../Datagrid').default;
    const DatagridStore = require('../../../containers/Datagrid').DatagridStore;
    DatagridStore.getActiveSetting = jest.fn();
    DatagridStore.getSortColumnSetting = jest.fn();
    DatagridStore.getSortOrderSetting = jest.fn();
    DatagridStore.getLimitSetting = jest.fn();

    DatagridStore.getActiveSetting.mockReturnValueOnce('some-uuid');
    DatagridStore.getSortColumnSetting.mockReturnValueOnce('title');
    DatagridStore.getSortOrderSetting.mockReturnValueOnce('desc');
    DatagridStore.getLimitSetting.mockReturnValueOnce(50);

    expect(Datagrid.getDerivedRouteAttributes({
        options: {
            resourceKey: 'test',
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
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const datagridStore = datagrid.instance().datagridStore;

    let toolbarConfig, item;
    toolbarConfig = toolbarFunction.call(datagrid.instance());
    item = toolbarConfig.items.find((item) => item.value === 'Delete');
    expect(item.disabled).toBe(true);

    datagridStore.selectionIds.push(1);
    toolbarConfig = toolbarFunction.call(datagrid.instance());
    item = toolbarConfig.items.find((item) => item.value === 'Delete');
    expect(item.disabled).toBe(false);
});

test('Should render the locale dropdown with the options from router', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                locales: ['en', 'de'],
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    datagrid.instance().locale = {
        get: function() {
            return 'de';
        },
    };

    const toolbarConfig = toolbarFunction.call(datagrid.instance());
    expect(toolbarConfig.locale.value).toBe('de');
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should pass apiOptions from router to the DatagridStore', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                locales: ['en', 'de'],
                adapters: ['table'],
                apiOptions: {
                    webspace: 'example',
                },
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const datagridStore = datagrid.instance().datagridStore;

    expect(datagridStore.options.webspace).toEqual('example');
});

test('Should pass locale and page observables to the DatagridStore', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                locales: ['en', 'de'],
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const datagridStore = datagrid.instance().datagridStore;

    expect(datagridStore.observableOptions).toHaveProperty('page');
    expect(datagridStore.observableOptions).toHaveProperty('locale');
});

test('Should not pass the locale observable to the DatagridStore if no locales are defined', () => {
    const Datagrid = require('../Datagrid').default;
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const datagridStore = datagrid.instance().datagridStore;

    expect(datagridStore.observableOptions).toHaveProperty('page');
    expect(datagridStore.observableOptions).not.toHaveProperty('locale');
});

test('Should delete selected items when delete button is clicked', () => {
    function getDeleteItem() {
        return toolbarFunction.call(datagrid.instance()).items.find((item) => item.value === 'Delete');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const datagridStore = datagrid.instance().datagridStore;
    datagridStore.selectionIds.push(1, 4, 6);
    datagridStore.deleteSelection.mockReturnValue(Promise.resolve());

    expect(getDeleteItem().loading).toBe(false);
    const clickPromise = getDeleteItem().onClick();
    expect(getDeleteItem().loading).toBe(true);

    return clickPromise.then(() => {
        expect(datagridStore.deleteSelection).toBeCalledWith();
        expect(getDeleteItem().loading).toBe(false);
    });
});

test('Should crash when deleting selected items returns a rejected promise', () => {
    function getDeleteItem() {
        return toolbarFunction.call(datagrid.instance()).items.find((item) => item.value === 'Delete');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Datagrid = require('../Datagrid').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Datagrid);
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'test',
                adapters: ['table'],
            },
        },
    };

    const datagrid = mount(<Datagrid router={router} />);
    const datagridStore = datagrid.instance().datagridStore;
    datagridStore.selectionIds.push(1, 4, 6);
    datagridStore.deleteSelection.mockReturnValue(Promise.reject());

    expect(getDeleteItem().loading).toBe(false);
    const clickPromise = getDeleteItem().onClick();
    expect(getDeleteItem().loading).toBe(true);

    return clickPromise.catch(() => {
        expect(getDeleteItem().loading).toBe(false);
    });
});
