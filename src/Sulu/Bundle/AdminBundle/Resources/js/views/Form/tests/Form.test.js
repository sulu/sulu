/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';
import AbstractFormToolbarAction from '../toolbarActions/AbstractFormToolbarAction';

jest.mock('../../../services/Initializer', () => jest.fn());
jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));
jest.mock('../toolbarActions/DeleteToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/SaveWithPublishingToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/SaveToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/TypeToolbarAction', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../containers/Form/registries/FieldRegistry', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../registries/FormToolbarActionRegistry', () => ({
    get: jest.fn(),
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
            formKey: 'snippets',
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route,
    };

    const form = shallow(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(resourceStore).toBe(form.instance().resourceStore);
});

test('Should not show the title if the titleVisible option is not given', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route,
    };

    const form = shallow(<Form resourceStore={resourceStore} route={route} router={router} title="Test 1" />);

    expect(form.find('h1')).toHaveLength(0);
});

test('Should show the title if the titleVisible option is set to true', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'snippets',
            titleVisible: true,
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route,
    };

    const form = shallow(<Form resourceStore={resourceStore} route={route} router={router} title="Test 2" />);

    expect(form.find('h1[children="Test 2"]')).toHaveLength(1);
});

test('Should create a new resourceStore if the passed resourceKey differs', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 10);
    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            locales: ['de', 'en'],
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            locales: ['de', 'en'],
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            idQueryParameter: 'contactId',
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route,
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = form.instance().resourceStore;

    expect(formResourceStore.idQueryParameter).toEqual('contactId');
});

test('Should add items defined in ToolbarActions to Toolbar', () => {
    const formToolbarActionRegistry = require('../registries/FormToolbarActionRegistry');
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1);

    class SaveToolbarAction extends AbstractFormToolbarAction {
        getToolbarItemConfig() {
            return {
                type: 'button',
                value: 'save',
            };
        }
    }

    class DeleteToolbarAction extends AbstractFormToolbarAction {
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

    formToolbarActionRegistry.get.mockImplementation((name) => {
        switch (name) {
            case 'save':
                return SaveToolbarAction;
            case 'delete':
                return DeleteToolbarAction;
        }
    });

    const route = {
        options: {
            formKey: 'snippets',
            toolbarActions: ['save', 'delete'],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

test('Should not add PublishIndicator if no publish status is available', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());

    expect(toolbarConfig.icons).toHaveLength(0);
});

test('Should add PublishIndicator if publish status is available showing draft', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});
    resourceStore.data = {
        publishedState: false,
        published: false,
    };

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

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
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});
    resourceStore.data = {
        publishedState: true,
        published: '2018-07-05',
    };

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

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
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});
    resourceStore.data = {
        publishedState: false,
        published: '2018-07-05',
    };

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());

    expect(toolbarConfig.icons).toHaveLength(1);
    const publishIndicator = shallow(toolbarConfig.icons[0]);

    expect(publishIndicator.instance().props).toEqual(expect.objectContaining({
        draft: true,
        published: true,
    }));
});

test('Should set and update locales defined in ToolbarActions', () => {
    const formToolbarActionRegistry = require('../registries/FormToolbarActionRegistry');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    class SaveToolbarAction extends AbstractFormToolbarAction {
        getToolbarItemConfig() {
            return {
                type: 'button',
                value: 'save',
            };
        }
    }

    formToolbarActionRegistry.get.mockImplementation((name) => {
        switch (name) {
            case 'save':
                return SaveToolbarAction;
        }
    });

    const route = {
        options: {
            formKey: 'snippets',
            toolbarActions: ['save'],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {locale: 'de'});
});

test('Should navigate to defined route on back button click with routerAttribuesToBackRoute', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            locales: [],
            routerAttributesToBackRoute: ['webspace'],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            webspace: 'sulu_io',
        },
        bind: jest.fn(),
        restore: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {locale: 'de', webspace: 'sulu_io'});
});

test('Should navigate to defined route on back button click with mixed routerAttribuesToBackRoute mapping', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            locales: [],
            routerAttributesToBackRoute: {0: 'webspace', 'id': 'active'},
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 4,
            webspace: 'sulu_io',
        },
        bind: jest.fn(),
        restore: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('test_route', {active: 4, locale: 'de', webspace: 'sulu_io'});
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
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

test('Should navigate to defined route after dialog has been confirmed', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    form.instance().resourceFormStore.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    const backRoute = {
        name: 'test_route',
    };
    const backRouteAttributes = {};

    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(false);
    expect(checkFormStoreDirtyStateBeforeNavigation({}, backRouteAttributes, router.navigate)).toEqual(false);
    form.update();
    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(true);

    form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('onCancel')();
    form.update();
    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(false);
    expect(router.navigate).not.toBeCalled();

    expect(checkFormStoreDirtyStateBeforeNavigation(backRoute, backRouteAttributes, router.navigate)).toEqual(false);
    form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('onConfirm')();
    form.update();
    expect(router.navigate).toBeCalledWith('test_route', backRouteAttributes);
});

test('Should navigate to defined route after dialog has been confirmed using restore', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        restore: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    form.instance().resourceFormStore.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    const backRoute = {
        name: 'test_route',
    };
    const backRouteAttributes = {};

    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(false);
    expect(checkFormStoreDirtyStateBeforeNavigation({}, backRouteAttributes, router.restore)).toEqual(false);
    form.update();
    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(true);

    form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('onCancel')();
    form.update();
    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(false);
    expect(router.restore).not.toBeCalled();

    expect(checkFormStoreDirtyStateBeforeNavigation(backRoute, backRouteAttributes, router.restore)).toEqual(false);
    form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('onConfirm')();
    form.update();
    expect(router.restore).toBeCalledWith('test_route', backRouteAttributes);
});

test('Should not close the window if formStore is still dirty', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    form.instance().resourceFormStore.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(false);
    expect(checkFormStoreDirtyStateBeforeNavigation()).toEqual(false);
});

test('Should close the window if formStore is not dirty', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    form.instance().resourceFormStore.dirty = false;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    expect(form.find('Dialog[title="sulu_admin.dirty_warning_dialog_title"]').prop('open')).toEqual(false);
    expect(checkFormStoreDirtyStateBeforeNavigation()).toEqual(true);
});

test('Should not render back button when no editLink is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = toolbarFunction.call(form.instance());
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should change locale by route navigation via locale chooser', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        name: 'sulu_admin.form',
        options: {
            backRoute: 'test_route',
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        navigate: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toBeCalledWith('sulu_admin.form', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Form);
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        options: {
            formKey: 'snippets',
            locales: ['en', 'de'],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

test('Should save form when submitted', () => {
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

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit('publish');
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith(
            'snippets',
            {value: 'Value'},
            {action: 'publish', id: 8, locale: 'en'}
        );
    });
});

test('Should save form when submitted with mapped router attributes', () => {
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

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            routerAttributesToFormStore: observable(['parentId', 'webspace']),
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit();
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put)
            .toBeCalledWith(
                'snippets',
                {value: 'Value'},
                {id: 8, locale: 'en', parentId: 3, webspace: 'sulu_io'}
            );
    });
});

test('Should save form when submitted with given apiOptions', () => {
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

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            apiOptions: {apiKey: 'api-option-value'},
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit();
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put)
            .toBeCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en', apiKey: 'api-option-value'});
    });
});

test('Should save form when submitted with mapped router attributes and given apiOptions', () => {
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

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            apiOptions: {apiKey: 'api-option-value'},
            routerAttributesToFormStore: {'parentId': 'id', '0': 'webspace', 1: 'title'},
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
            parentId: 3,
            webspace: 'sulu_io',
            title: 'Sulu is awesome',
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit();
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith(
            'snippets',
            {value: 'Value'},
            {id: 8, locale: 'en', apiKey: 'api-option-value', webspace: 'sulu_io', title: 'Sulu is awesome'}
        );
    });
});

test('Should save form when submitted with mapped named router attributes and given apiOptions', () => {
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

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            apiOptions: {apiKey: 'api-option-value'},
            routerAttributesToFormStore: {'id': 'parentId'},
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit();
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith(
            'snippets', {value: 'Value'}, {id: 8, locale: 'en', apiKey: 'api-option-value', parentId: 8}
        );
    });
});

test('Should show warning when form is submitted but already changed on the server and cancel', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(Promise.resolve({code: 1102}))});
    ResourceRequester.put.mockReturnValue(putPromise);
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit('publish');
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith(
            'snippets',
            {value: 'Value'},
            {action: 'publish', id: 8, locale: 'en'}
        );

        ResourceRequester.put.mockClear();

        expect(form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"]').prop('open')).toEqual(false);

        return putPromise.catch(() => {
            setTimeout(() => {
                form.update();
                expect(form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"]').prop('open'))
                    .toEqual(true);
                form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"] Button[skin="secondary"]')
                    .simulate('click');
                form.update();
                expect(form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"]').prop('open'))
                    .toEqual(false);
                expect(ResourceRequester.put).not.toBeCalled();
                done();
            });
        });
    });
});

test('Should show warning when form is submitted but already changed on the server and confirm', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(Promise.resolve({code: 1102}))});
    ResourceRequester.put.mockReturnValue(putPromise);
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        form.find('Form').at(1).instance().submit('publish');
        expect(resourceStore.destroy).not.toBeCalled();
        expect(ResourceRequester.put).toBeCalledWith(
            'snippets',
            {value: 'Value'},
            {action: 'publish', id: 8, locale: 'en'}
        );

        ResourceRequester.put.mockClear();

        expect(form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"]').prop('open')).toEqual(false);

        return putPromise.catch(() => {
            setTimeout(() => {
                form.update();
                expect(form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"]').prop('open'))
                    .toEqual(true);
                form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"] Button[skin="primary"]')
                    .simulate('click');
                form.update();
                expect(form.find('Dialog[title="sulu_admin.has_changed_warning_dialog_title"]').prop('open'))
                    .toEqual(false);

                expect(ResourceRequester.put).toBeCalledWith(
                    'snippets',
                    {value: 'Value'},
                    {action: 'publish', force: true, id: 8, locale: 'en'}
                );
                done();
            });
        });
    });
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
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
        expect(ResourceRequester.put).toBeCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en'});
        expect(form.instance().showSuccess.get()).toEqual(true);
        done();
    });
});

test('Should set showSuccess flag after calling onSuccess', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(form.instance().showSuccess.get()).toEqual(false);
    expect(form.find('Form').at(1).prop('onSuccess')).toBeInstanceOf(Function);
    form.find('Form').at(1).prop('onSuccess')();
    expect(form.instance().showSuccess.get()).toEqual(true);
});

test('Should show error if form has been tried to save although it is not valid', () => {
    const Form = require('../Form').default;
    const ResourceRequester = require('../../../services/ResourceRequester');
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({required: ['title']});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return jsonSchemaPromise.then(() => {
            form.find('Form').at(1).instance().submit();
            expect(resourceStore.destroy).not.toBeCalled();
            expect(ResourceRequester.put).not.toBeCalled();
            expect(form.instance().errors).toHaveLength(1);
        });
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
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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
        expect(ResourceRequester.put).toBeCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en'});
        expect(form.instance().errors).toEqual([error]);
        done();
    });
});

test('Should save form when submitted and redirect to editRoute', () => {
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

    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            editRoute: 'editRoute',
            formKey: 'snippets',
            locales: [],
            routerAttributesToEditRoute: ['webspace'],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return form.find('Form').at(1).instance().submit().then(() => {
            expect(resourceStore.destroy).toBeCalled();
            expect(ResourceRequester.post).toBeCalledWith('snippets', {value: 'Value'}, {});
            expect(router.navigate)
                .toBeCalledWith('editRoute', {id: undefined, locale: undefined, webspace: 'sulu_io'});
        });
    });
});

test('Should save form when submitted and redirect to editRoute', () => {
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

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            editRoute: 'editRoute',
            formKey: 'snippets',
            locales: [],
            routerAttributesToEditRoute: {0: 'webspace', 'id': 'active'},
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
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

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return form.find('Form').at(1).instance().submit().then(() => {
            expect(resourceStore.destroy).toBeCalled();
            expect(ResourceRequester.post).toBeCalledWith('snippets', {value: 'Value'}, {});
            expect(router.navigate)
                .toBeCalledWith('editRoute', {active: 8, id: undefined, locale: undefined, webspace: 'sulu_io'});
        });
    });
});

test('Should pass router, store and schema handler to FormContainer', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {},
    };

    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formContainer = form.find('Form').at(1);

    expect(formContainer.prop('router')).toEqual(router);
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
            formKey: 'snippets',
            locales: [],
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        route,
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);
    const locale = form.find('Form').at(1).prop('store').locale;

    expect(router.bind).toBeCalledWith('locale', locale);

    const resourceFormStore = form.instance().resourceFormStore;
    resourceFormStore.destroy = jest.fn();

    form.unmount();
    expect(resourceFormStore.destroy).toBeCalled();
    expect(resourceStore.destroy).not.toBeCalled();
});

test('Should destroy the own resourceStore if existing on unmount', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 11);
    resourceStore.destroy = jest.fn();

    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route,
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
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
            formKey: 'snippets',
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        route,
    };

    mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(router.bind).not.toBeCalled();
});

test('Should add and remove the UpdateRouteHook on mounting and unmounting', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        route,
    };

    const checkFormStoreDirtyStateBeforeNavigationDisposerSpy = jest.fn();
    router.addUpdateRouteHook.mockImplementationOnce(() => checkFormStoreDirtyStateBeforeNavigationDisposerSpy);
    const form = mount(<Form resourceStore={resourceStore} route={route} router={router} />);

    const checkFormStoreDirtyStateBeforeNavigation = form.instance().checkFormStoreDirtyStateBeforeNavigation;

    expect(router.addUpdateRouteHook).toBeCalledWith(checkFormStoreDirtyStateBeforeNavigation, 2048);
    expect(checkFormStoreDirtyStateBeforeNavigationDisposerSpy).not.toBeCalledWith();

    form.unmount();
    expect(checkFormStoreDirtyStateBeforeNavigationDisposerSpy).toBeCalledWith();
});

test('Should throw an error if the resourceStore is not passed', () => {
    const router = {
        addUpdateRouteHook: jest.fn(),
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

test('Should throw an error if no formKey is passed', () => {
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route: {
            options: {
                toolbarActions: [],
            },
        },
    };
    const Form = require('../Form').default;
    expect(() => shallow(<Form resourceStore={resourceStore} router={router} />)).toThrow(/"formKey"/);
});
