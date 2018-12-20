/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';

jest.mock('jexl', () => ({
    eval: jest.fn().mockImplementation((expression) => {
        if (undefined === expression) {
            throw new Error('Expression cannot be undefined');
        }

        return Promise.resolve(expression === 'nodeType == 1');
    }),
}));

jest.mock('../../../services/Initializer', () => jest.fn());
jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));
jest.mock('../../../containers/Sidebar/withSidebar', () => jest.fn((Component) => Component));
jest.mock('../toolbarActions/DeleteToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/SaveWithPublishingToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/SaveToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/TypeToolbarAction', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.delete':
                return 'Delete';
        }
    },
}));

jest.mock('../../../containers/Form/registries/FieldRegistry', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../registries/ToolbarActionRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.save':
                return 'Save';
        }
    },
}));

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn(),
    post: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve({})),
}));

beforeEach(() => {
    jest.resetModules();
});

test('Should reuse the passed resourceStore if the passed resourceKey is the same', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(resourceStore).toBe(form.instance().resourceStore);
});

test('Should reload the passed resourceStore if some data has been there before showing the form', () => {
    const Form = require('../Form').default;
    const ResourceRequester = require('../../../services/ResourceRequester');
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        route,
    };

    resourceStore.data = {value: 'something'};
    mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(ResourceRequester.get).toHaveBeenCalledTimes(2);
});

test('Should create a new resourceStore if the passed resourceKey differs', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;

    expect(resourceStore).not.toBe(formResourceStore);
    expect(resourceStore.resourceKey).toEqual('snippets');
    expect(formResourceStore.resourceKey).toEqual('pages');
    expect(formResourceStore.locale).toEqual(undefined);
});

test('Should create a new resourceStore if the passed resourceKey differs with locale', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const locale = observable.box('en');
    const resourceStore = new ResourceStore('snippets', 10, {locale});
    const route = {
        options: {
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        bind: jest.fn(),
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;

    expect(resourceStore).not.toBe(formResourceStore);
    expect(resourceStore.resourceKey).toEqual('snippets');
    expect(formResourceStore.resourceKey).toEqual('pages');
    expect(formResourceStore.locale.get()).toEqual('en');
});

test('Should create a new resourceStore if the passed resourceKey differs with own locales', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10, {});
    const route = {
        options: {
            locales: ['de', 'en'],
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        bind: jest.fn(),
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;

    expect(resourceStore).not.toBe(formResourceStore);
    expect(resourceStore.resourceKey).toEqual('snippets');
    expect(formResourceStore.resourceKey).toEqual('pages');
    expect(formResourceStore.locale.get()).toEqual(undefined);
});

test('Should create a new resourceStore if the passed resourceKey differs with own locales including locale', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const locale = observable.box('en');
    const resourceStore = new ResourceStore('snippets', 10, {locale});
    const route = {
        options: {
            locales: ['de', 'en'],
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        bind: jest.fn(),
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;

    expect(resourceStore).not.toBe(formResourceStore);
    expect(resourceStore.resourceKey).toEqual('snippets');
    expect(formResourceStore.resourceKey).toEqual('pages');
    expect(formResourceStore.locale).toBe(locale);
});

test('Should instantiate the ResourceStore with the idQueryParameter if given', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            idQueryParameter: 'contactId',
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;

    expect(formResourceStore.idQueryParameter).toEqual('contactId');
});

test('Should add items defined in ToolbarActions to Toolbar', () => {
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry');
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1);

    class SaveToolbarAction extends AbstractToolbarAction {
        getToolbarItemConfig() {
            return {
                type: 'button',
                value: 'save',
            };
        }
    }

    class DeleteToolbarAction extends AbstractToolbarAction {
        getNode() {
            return <p key="delete">This is the delete button test!</p>;
        }

        getToolbarItemConfig() {
            return {
                type: 'button',
                value: 'delete',
            };
        }
    }

    toolbarActionRegistry.get.mockImplementation((name) => {
        switch (name) {
            case 'save':
                return SaveToolbarAction;
            case 'delete':
                return DeleteToolbarAction;
        }
    });

    const route = {
        options: {
            toolbarActions: ['save', 'delete'],
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(form.html()).toEqual(expect.stringContaining('<p>This is the delete button test!</p>'));

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.items).toEqual([
        {type: 'button', value: 'save'},
        {type: 'button', value: 'delete'},
    ]);
});

test('Should initialize preview sidebar', () => {
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const Form = require('../Form').default;
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);
    resourceStore.loading = false;

    const route = {
        options: {
            toolbarActions: [],
            preview: 'nodeType == 1',
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    // trigger evaluation
    sidebarFunction.call(form.instance());

    setTimeout(() => {
        const sidebarConfig = sidebarFunction.call(form.instance());
        expect(sidebarConfig.view).toEqual('sulu_preview.preview');
        expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
        expect(sidebarConfig.props.router).toEqual(router);
        expect(sidebarConfig.props.formStore).toBeDefined();
    }, 0);
});

test('Should not initialize preview sidebar when expression evaluates to false', () => {
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const Form = require('../Form').default;
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);
    resourceStore.loading = false;

    const route = {
        options: {
            toolbarActions: [],
            preview: 'nodeType == 2',
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    // trigger evaluation
    sidebarFunction.call(form.instance());

    setTimeout(() => {
        const sidebarConfig = sidebarFunction.call(form.instance());
        expect(sidebarConfig).toEqual(null);
    }, 0);
});

test('Should not initialize preview sidebar when option is not set', () => {
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const Form = require('../Form').default;
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);
    resourceStore.loading = false;

    const route = {
        options: {
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    // trigger evaluation
    sidebarFunction.call(form.instance());

    setTimeout(() => {
        const sidebarConfig = sidebarFunction.call(form.instance());
        expect(sidebarConfig).toEqual(null);
    }, 0);
});

test('Should not add PublishIndicator if no publish status is available', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());

    expect(toolbarConfig.icons).toHaveLength(0);
});

test('Should add PublishIndicator if publish status is available showing draft', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});
    resourceStore.data = {
        publishedState: false,
        published: false,
    };

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());

    expect(toolbarConfig.icons).toHaveLength(1);
    const publishIndicator = shallow(toolbarConfig.icons[0]);

    expect(publishIndicator.instance().props).toEqual(expect.objectContaining({
        draft: true,
        published: false,
    }));
});

test('Should add PublishIndicator if publish status is available showing published', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});
    resourceStore.data = {
        publishedState: true,
        published: '2018-07-05',
    };

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());

    expect(toolbarConfig.icons).toHaveLength(1);
    const publishIndicator = shallow(toolbarConfig.icons[0]);

    expect(publishIndicator.instance().props).toEqual(expect.objectContaining({
        draft: false,
        published: true,
    }));
});

test('Should add PublishIndicator if publish status is available showing published and draft', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});
    resourceStore.data = {
        publishedState: false,
        published: '2018-07-05',
    };

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());

    expect(toolbarConfig.icons).toHaveLength(1);
    const publishIndicator = shallow(toolbarConfig.icons[0]);

    expect(publishIndicator.instance().props).toEqual(expect.objectContaining({
        draft: true,
        published: true,
    }));
});

test('Should set and update locales defined in ToolbarActions', () => {
    const toolbarActionRegistry = require('../registries/ToolbarActionRegistry');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    class SaveToolbarAction extends AbstractToolbarAction {
        getToolbarItemConfig() {
            return {
                type: 'button',
                value: 'save',
            };
        }
    }

    toolbarActionRegistry.get.mockImplementation((name) => {
        switch (name) {
            case 'save':
                return SaveToolbarAction;
        }
    });

    const route = {
        options: {
            toolbarActions: ['save'],
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form locales={[]} resourceStore={resourceStore} route={route} router={router} />);
    expect(form.instance().toolbarActions[0].locales).toEqual([]);

    form.setProps({locales: ['en', 'de']});
    expect(form.instance().toolbarActions[0].locales).toEqual(['en', 'de']);
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            backRoute: 'test_route',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {locale: 'de'});
});

test('Should navigate to defined route on back button click without locale', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backRoute: 'test_route',
            toolbarActions: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {});
});

test('Should not render back button when no editLink is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            toolbarActions: [],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should change locale in form store via locale chooser', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            backRoute: 'test_route',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.locale.get()).toBe('en');
});

test('Should show locales from router options in toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            locales: ['en', 'de'],
            toolbarActions: [],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form locales={[]} resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should show locales from props in toolbar if route has no locales', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            toolbarActions: [],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form locales={['en', 'de']} resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should not show a locale chooser if no locales are passed in router options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            toolbarActions: [],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.locale).toBe(undefined);
});

test('Should initialize the ResourceStore with a schema', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {
            id: 12,
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const schemaPromise = Promise.resolve({
        title: {},
        slogan: {},
    });
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    return Promise.all([schemaTypesPromise, schemaPromise]).then(() => {
        expect(resourceStore.resourceKey).toBe('snippets');
        expect(resourceStore.id).toBe(12);
        expect(resourceStore.data).toEqual({
            title: undefined,
            slogan: undefined,
        });
    });
});

test('Should save form when submitted', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit('publish');
            expect(resourceStore.destroy).not.toBeCalled();
            expect(ResourceRequester.put).toBeCalledWith(
                'snippets',
                8,
                {value: 'Value'},
                {action: 'publish', locale: 'en'}
            );
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should save form when submitted with mapped router attributes', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            locales: [],
            routerAttributesToFormStore: ['parentId', 'webspace'],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
            parentId: 3,
            webspace: 'sulu_io',
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit();
            expect(resourceStore.destroy).not.toBeCalled();
            expect(ResourceRequester.put)
                .toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en', parentId: 3, webspace: 'sulu_io'});
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should save form when submitted with given apiOptions', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            locales: [],
            apiOptions: {apiKey: 'api-option-value'},
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit();
            expect(resourceStore.destroy).not.toBeCalled();
            expect(ResourceRequester.put)
                .toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en', apiKey: 'api-option-value'});
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should save form when submitted with mapped router attributes and given apiOptions', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            locales: [],
            apiOptions: {apiKey: 'api-option-value'},
            routerAttributesToFormStore: ['parentId'],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
            parentId: 3,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit();
            expect(resourceStore.destroy).not.toBeCalled();
            expect(ResourceRequester.put).toBeCalledWith(
                'snippets', 8, {value: 'Value'}, {locale: 'en', apiKey: 'api-option-value', parentId: 3}
            );
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should set showSuccess flag after form submission', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    form.find('Form').at(1).instance().submit().then(() => {
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en'});
        expect(form.instance().showSuccess.get()).toEqual(true);
        done();
    });
});

test('Should keep errors after form submission has failed', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    const error = {code: 100, message: 'Something went wrong'};
    const errorPromise = Promise.resolve(error);
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(errorPromise)});
    ResourceRequester.put.mockReturnValue(putPromise);
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    form.find('Form').at(1).instance().submit().then(() => {
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en'});
        expect(form.instance().errors).toEqual([error]);
        done();
    });
});

test('Should save form when submitted and redirect to editRoute', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            editRoute: 'editRoute',
            locales: [],
            routerAttributesToEditRoute: ['webspace'],
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {
            id: 8,
            webspace: 'sulu_io',
        },
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit().then(() => {
                expect(resourceStore.destroy).toBeCalled();
                expect(ResourceRequester.post).toBeCalledWith('snippets', {value: 'Value'}, {});
                expect(router.navigate)
                    .toBeCalledWith('editRoute', {id: undefined, locale: undefined, webspace: 'sulu_io'});
                done();
            });
        });
    });

    jsonSchemaResolve({});
});

test('Should pass store and schema handler to FormContainer', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formContainer = form.find('Form').at(1);

    expect(formContainer.prop('store').resourceStore).toEqual(resourceStore);
    expect(formContainer.prop('onSubmit')).toBeInstanceOf(Function);
});

test('Should destroy the store on unmount', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12, {locale: observable.box()});
    resourceStore.destroy = jest.fn();
    const route = {
        options: {
            resourceKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const locale = form.find('Form').at(1).prop('store').locale;

    expect(router.bind).toBeCalledWith('locale', locale);

    const formStore = form.instance().formStore;
    formStore.destroy = jest.fn();

    form.unmount();
    expect(formStore.destroy).toBeCalled();
    expect(resourceStore.destroy).not.toBeCalled();
});

test('Should destroy the own resourceStore if existing on unmount', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 11);
    resourceStore.destroy = jest.fn();

    const route = {
        options: {
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        attributes: {},
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;
    formResourceStore.destroy = jest.fn();

    form.unmount();
    expect(resourceStore.destroy).not.toBeCalled();
    expect(formResourceStore.destroy).toBeCalled();
});

test('Should not bind the locale if no locales have been passed via options', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);
    const route = {
        options: {
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(router.bind).not.toBeCalled();

    form.unmount();
    expect(router.unbind).not.toBeCalled();
});

test('Should throw an error if the resourceStore is not passed for some reason', () => {
    const router = {
        attributes: {},
        route: {
            options: {
                toolbarActions: [],
            },
        },
    };
    const Form = require('../Form').default;
    expect(() => shallow(<Form router={router} />)).toThrow(/"ResourceTabs"/);
});
