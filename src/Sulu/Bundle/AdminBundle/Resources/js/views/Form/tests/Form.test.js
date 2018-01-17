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
}));

jest.mock('../../../containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));

beforeEach(() => {
    jest.resetModules();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable()});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {locale: 'de'});
});

test('Should navigate to defined route on back button click without locale', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {});
});

test('Should not render back button when no editLink is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should change locale in form store via locale chooser', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable()});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(form);
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.locale.get()).toBe('en');
});

test('Should show locales from router options in toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable()});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['en', 'de'],
            },
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should not show a locale chooser if no locales are passed in router options', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippet', 1);

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.locale).toBe(undefined);
});

test('Should initialize the ResourceStore with a schema', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {
            id: 12,
        },
    };

    const promise = Promise.resolve({
        title: {},
        slogan: {},
    });
    metadataStore.getSchema.mockReturnValue(promise);

    mount(<Form router={router} resourceStore={resourceStore} />);

    return promise.then(() => {
        expect(resourceStore.resourceKey).toBe('snippets');
        expect(resourceStore.id).toBe(12);
        expect(resourceStore.data).toEqual({
            title: null,
            slogan: null,
        });
    });
});

test('Should render save button disabled only if form is not dirty', () => {
    function getSaveItem() {
        return toolbarFunction.call(form).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const resourceStore = new ResourceStore('snippets', 12);

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);

    expect(getSaveItem().disabled).toBe(true);

    resourceStore.dirty = true;
    expect(getSaveItem().disabled).toBe(false);
});

test('Should save form when submitted', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable()});

    const promise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(promise);

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;

    return promise.then(() => {
        form.find('Form').at(1).simulate('submit');
        expect(ResourceRequester.put).toBeCalledWith('snippets', 8, {value: 'Value'}, {locale: 'en'});
    });
});

test('Should pass store and schema handler to FormContainer', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };

    const form = shallow(<Form router={router} resourceStore={resourceStore} />);
    const formContainer = form.find('Form');

    expect(formContainer.prop('store').resourceStore).toEqual(resourceStore);
    expect(formContainer.prop('onSubmit')).toBeInstanceOf(Function);
});

test('Should render save button loading only if form is saving', () => {
    function getSaveItem() {
        return toolbarFunction.call(form).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<Form router={router} resourceStore={resourceStore} />).get(0);

    expect(getSaveItem().loading).toBe(false);

    resourceStore.saving = true;
    expect(getSaveItem().loading).toBe(true);
});

test('Should unbind the binding and destroy the store on unmount', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12, {locale: observable()});
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                locales: [],
            },
        },
        attributes: {},
    };

    const form = mount(<Form router={router} resourceStore={resourceStore} />);
    const locale = form.find('Form').at(1).prop('store').locale;

    expect(router.bind).toBeCalledWith('locale', locale);

    form.unmount();
    expect(router.unbind).toBeCalledWith('locale', locale);
});

test('Should not bind the locale if no locales have been passed via options', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
            },
        },
        attributes: {},
    };

    const form = mount(<Form router={router} resourceStore={resourceStore} />);

    expect(router.bind).not.toBeCalled();

    form.unmount();
    expect(router.unbind).not.toBeCalled();
});
