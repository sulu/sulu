/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

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
    post: jest.fn().mockReturnValue(Promise.resolve()),
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
        },
    };
    const router = {
        attributes: {},
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} router={router} route={route} />);

    expect(resourceStore).toBe(form.instance().resourceStore);
});

test('Should reuse the passed resourceStore if the passed resourceKey differs', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            resourceKey: 'pages',
        },
    };
    const router = {
        attributes: {},
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} router={router} route={route} />);
    const formResourceStore = form.instance().resourceStore;

    expect(resourceStore).not.toBe(formResourceStore);
    expect(resourceStore.resourceKey).toEqual('snippets');
    expect(formResourceStore.resourceKey).toEqual('pages');
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            backRoute: 'test_route',
            locales: [],
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {locale: 'de'});
});

test('Should navigate to defined route on back button click without locale', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backRoute: 'test_route',
        },
    };
    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {});
});

test('Should not render back button when no editLink is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {},
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should change locale in form store via locale chooser', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            backRoute: 'test_route',
            locales: [],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.locale.get()).toBe('en');
});

test('Should show locales from router options in toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            locales: ['en', 'de'],
        },
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should show loading templates chooser in toolbar while types are loading', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {},
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig).toMatchSnapshot();
});

test('Should change template on click in template chooser', (done) => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');

    const resourceStore = new ResourceStore('snippet', 1);
    resourceStore.loading = false;
    resourceStore.data.template = 'sidebar';

    const route = {
        options: {},
    };
    const router = {
        attributes: {},
        route,
    };

    const typesPromise = Promise.resolve({
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    });
    metadataStore.getSchemaTypes.mockReturnValue(typesPromise);

    const sidebarPromise = Promise.resolve({
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    });
    const footerMetadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
    };
    const footerPromise = Promise.resolve(footerMetadata);
    metadataStore.getSchema.mockImplementation((resourceKey, type) => {
        switch (type) {
            case 'sidebar':
                return sidebarPromise;
            case 'footer':
                return footerPromise;
        }
    });

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    Promise.all([typesPromise, sidebarPromise, footerPromise, jsonSchemaPromise]).then(() => {
        const toolbarOptions = withToolbar.mock.calls[0][1].call(form.instance());
        toolbarOptions.items[1].onChange('footer');
        const schemaPromise = Promise.resolve(footerMetadata);
        metadataStore.getSchema.mockReturnValue(schemaPromise);

        Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
            form.update();
            expect(form.find('Item')).toHaveLength(1);
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should show templates chooser in toolbar if types are available', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');

    const route = {
        options: {},
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const typesPromise = Promise.resolve({
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    });
    metadataStore.getSchemaTypes.mockReturnValue(typesPromise);

    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    return typesPromise.then(() => {
        const toolbarConfig = toolbarFunction.call(form.instance());
        expect(toolbarConfig).toMatchSnapshot();
    });
});

test('Should not show templates chooser in toolbar if types are not available', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');

    const route = {
        options: {},
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const typesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(typesPromise);

    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    return typesPromise.then(() => {
        const toolbarConfig = toolbarFunction.call(form.instance());
        expect(toolbarConfig).toMatchSnapshot();
    });
});

test('Should not show a locale chooser if no locales are passed in router options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {},
    };
    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

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

    mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    return Promise.all([schemaTypesPromise, schemaPromise]).then(() => {
        expect(resourceStore.resourceKey).toBe('snippets');
        expect(resourceStore.id).toBe(12);
        expect(resourceStore.data).toEqual({
            title: undefined,
            slogan: undefined,
        });
    });
});

test('Should render save button disabled only if form is not dirty', () => {
    function getSaveItem() {
        return toolbarFunction.call(form.instance()).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {},
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    expect(getSaveItem().disabled).toBe(true);

    resourceStore.dirty = true;
    expect(getSaveItem().disabled).toBe(false);
});

test('Should save form when submitted', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve());
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
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit();
            expect(resourceStore.destroy).not.toBeCalled();
            expect(ResourceRequester.put).toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en'});
            done();
        });
    });

    jsonSchemaResolve({});
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
        },
    };
    const router = {
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit();
            expect(resourceStore.destroy).toBeCalled();
            expect(ResourceRequester.post).toBeCalledWith('snippets', {value: 'Value'}, {});
            done();
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
        },
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {},
    };

    const form = shallow(<Form router={router} route={route} resourceStore={resourceStore} />);
    const formContainer = form.find('Form');

    expect(formContainer.prop('store').resourceStore).toEqual(resourceStore);
    expect(formContainer.prop('onSubmit')).toBeInstanceOf(Function);
});

test('Should render save button loading only if form is saving', () => {
    function getSaveItem() {
        return toolbarFunction.call(form.instance()).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {},
    };
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    expect(getSaveItem().loading).toBe(false);

    resourceStore.saving = true;
    expect(getSaveItem().loading).toBe(true);
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
        },
    };
    const router = {
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);
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
        },
    };
    const router = {
        attributes: {},
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} router={router} route={route} />);
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
        },
    };
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form router={router} route={route} resourceStore={resourceStore} />);

    expect(router.bind).not.toBeCalled();

    form.unmount();
    expect(router.unbind).not.toBeCalled();
});

test('Should throw an error if the resourceStore is not passed for some reason', () => {
    const router = {
        attributes: {},
        route: {
            options: {},
        },
    };
    const Form = require('../Form').default;
    expect(() => shallow(<Form router={router} />)).toThrow(/"ResourceTabs"/);
});
