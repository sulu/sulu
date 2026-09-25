/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {act, render, screen, waitFor, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AbstractFormToolbarAction from '../toolbarActions/AbstractFormToolbarAction';

let mockFormContainer;
let mockFormContainerProps;

jest.mock('../../../services/initializer', () => jest.fn());
jest.mock('../../../containers/Toolbar/stores/toolbarStorePool', () => ({
    __esModule: true,
    DEFAULT_STORE_KEY: 'default',
    default: {
        setToolbarConfig: jest.fn(),
    },
}));

jest.mock('../../../containers/Form/Form', () => {
    const React = require('react');
    const FormContainer = jest.requireActual('../../../containers/Form/Form').default;

    const FormContainerMock = React.forwardRef((props, forwardedRef) => {
        mockFormContainerProps = props;

        function handleRef(formContainer) {
            mockFormContainer = formContainer;

            if (typeof forwardedRef === 'function') {
                forwardedRef(formContainer);
            }
        }

        return React.createElement(FormContainer, {...props, ref: handleRef});
    });
    FormContainerMock.displayName = 'FormContainerMock';

    return FormContainerMock;
});
jest.mock('../toolbarActions/DeleteToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/SaveWithPublishingToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/SaveToolbarAction', () => jest.fn());
jest.mock('../toolbarActions/TypeToolbarAction', () => jest.fn());

jest.mock('../../../utils/Translator');

jest.mock('../../../containers/Form/registries/fieldRegistry', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../registries/formToolbarActionRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../../../services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
    put: jest.fn(),
    post: jest.fn(),
    delete: jest.fn(),
}));

jest.mock('../../../containers/Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve(null)),
}));

beforeEach(() => {
    jest.resetModules();
    mockFormContainer = undefined;
    mockFormContainerProps = undefined;
});

function renderFormElement(element) {
    const {
        router,
    } = element.props;

    if (router && router.addUpdateRouteHook && router.addUpdateRouteHook.mockReturnValue) {
        router.addUpdateRouteHook.mockReturnValue(jest.fn());
    }

    return render(element);
}

function getToolbarConfig() {
    const toolbarStorePool = require('../../../containers/Toolbar/stores/toolbarStorePool').default;
    const calls = toolbarStorePool.setToolbarConfig.mock.calls;

    return calls[calls.length - 1][1];
}

function getDialog(title) {
    return screen.getByText(title).closest('.dialogContainer');
}

function queryDialog(title) {
    const dialogTitle = screen.queryByText(title);

    return dialogTitle && dialogTitle.closest('.dialogContainer');
}

function expectDialogClosed(title) {
    const dialog = queryDialog(title);

    if (!dialog) {
        expect(dialog).toBeNull();
        return;
    }

    expect(dialog).not.toHaveClass('open');
}

async function clickDialogButton(user, title, buttonName) {
    const dialog = getDialog(title);

    if (!dialog) {
        throw new Error('Expected dialog');
    }

    await user.click(within(dialog).getByRole('button', {name: buttonName}));
}

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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(mockFormContainerProps.store.resourceStore).toBe(resourceStore);
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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} title="Test 1" />);

    expect(screen.queryByRole('heading', {name: 'Test 1'})).not.toBeInTheDocument();
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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} title="Test 2" />);

    expect(screen.getByRole('heading', {name: 'Test 2'})).toBeInTheDocument();
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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = mockFormContainerProps.store.resourceStore;

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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = mockFormContainerProps.store.resourceStore;

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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = mockFormContainerProps.store.resourceStore;

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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = mockFormContainerProps.store.resourceStore;

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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = mockFormContainerProps.store.resourceStore;

    expect(formResourceStore.idQueryParameter).toEqual('contactId');
});

test('Should not instantiate a CollaborationStore if it is an add form', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets');
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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const ResourceRequester = require('../../../services/ResourceRequester');
    expect(ResourceRequester.put).not.toHaveBeenCalled();
});

test('Should instantiate a CollaborationStore if it is an edit form and show ', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 6);
    const ResourceRequester = require('../../../services/ResourceRequester');

    const collaborations = [
        {
            fullName: 'Max Mustermann',
        },
        {
            fullName: 'Erika Mustermann',
        },
    ];
    const collaborationsPromise = Promise.resolve({_embedded: {collaborations}});
    ResourceRequester.put.mockReturnValue(collaborationsPromise);

    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'snippets',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 6,
        },
        route,
    };

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    expect(ResourceRequester.put).toHaveBeenCalledWith('collaborations', null, {id: 6, resourceKey: 'snippets'});

    return collaborationsPromise.then(() => {
        expect(getToolbarConfig().warnings).toEqual(['sulu_admin.form_used_by Max Mustermann, Erika Mustermann']);
    });
});

test('Throw error if options are not passed correctly', () => {
    const formToolbarActionRegistry = require('../registries/formToolbarActionRegistry');
    const Form = require('../Form').default;
    Form.prototype.updateRouteHookDisposer = jest.fn();
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
    expect(() => renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />))
        .toThrow('but string was given');
});

test('Should add items defined in ToolbarActions to Toolbar with options', () => {
    const formToolbarActionRegistry = require('../registries/formToolbarActionRegistry');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const SaveToolbarAction = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn();
    });

    const DeleteToolbarAction = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn();
    });

    const EditToolbarAction = jest.fn(function() {
        this.destroy = jest.fn();
        this.getNode = jest.fn();
    });

    formToolbarActionRegistry.get.mockImplementation((name) => {
        switch (name) {
            case 'save':
                return SaveToolbarAction;
            case 'delete':
                return DeleteToolbarAction;
            case 'edit':
                return EditToolbarAction;
        }
    });

    const route = {
        options: {
            formKey: 'snippets',
            toolbarActions: [
                {
                    type: 'save',
                    options: {test1: 'value1'},
                },
                {
                    type: 'delete',
                    options: {test2: 'value2'},
                },
                {
                    type: 'edit',
                    options: {},
                },
            ],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const resourceFormStore = mockFormContainerProps.store;

    expect(SaveToolbarAction).toHaveBeenCalledWith(
        resourceFormStore,
        expect.any(Object),
        router,
        undefined,
        {test1: 'value1'},
        resourceStore
    );

    expect(DeleteToolbarAction).toHaveBeenCalledWith(
        resourceFormStore,
        expect.any(Object),
        router,
        undefined,
        {test2: 'value2'},
        resourceStore
    );

    expect(EditToolbarAction).toHaveBeenCalledWith(
        resourceFormStore,
        expect.any(Object),
        router,
        undefined,
        {},
        resourceStore
    );
});

test('Should not add PublishIndicator if no publish status is available', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();

    expect(toolbarConfig.icons).toHaveLength(0);
});

test('Should add PublishIndicator if publish status is available showing draft', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();

    expect(toolbarConfig.icons).toHaveLength(1);

    expect(toolbarConfig.icons[0].props).toEqual(expect.objectContaining({
        draft: true,
        published: false,
    }));
});

test('Should add PublishIndicator if publish status is available showing published', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();

    expect(toolbarConfig.icons).toHaveLength(1);

    expect(toolbarConfig.icons[0].props).toEqual(expect.objectContaining({
        draft: false,
        published: true,
    }));
});

test('Should add PublishIndicator if publish status is available showing published and draft', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();

    expect(toolbarConfig.icons).toHaveLength(1);

    expect(toolbarConfig.icons[0].props).toEqual(expect.objectContaining({
        draft: true,
        published: true,
    }));
});

test('Should set and update locales defined in ToolbarActions', () => {
    const formToolbarActionRegistry = require('../registries/formToolbarActionRegistry');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);
    let toolbarAction;

    class SaveToolbarAction extends AbstractFormToolbarAction {
        constructor(...args) {
            super(...args);
            toolbarAction = this;
        }

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
            toolbarActions: [{type: 'save', options: []}],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        route,
        attributes: {},
    };

    const {rerender} = renderFormElement(
        <Form locales={[]} resourceStore={resourceStore} route={route} router={router} />
    );
    expect(toolbarAction.locales).toEqual([]);

    rerender(<Form locales={['en', 'de']} resourceStore={resourceStore} route={route} router={router} />);
    expect(toolbarAction.locales).toEqual(['en', 'de']);
});

test('Should navigate to defined route on back button click', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('test_route', {locale: 'de'});
});

test('Should navigate to defined route on back button click with routerAttribuesToBackRoute', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            backView: 'test_route',
            formKey: 'snippets',
            locales: [],
            routerAttributesToBackView: ['webspace'],
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('test_route', {locale: 'de', webspace: 'sulu_io'});
});

test('Should navigate to defined route on back button click with mixed routerAttribuesToBackRoute mapping', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});

    const route = {
        options: {
            backView: 'test_route',
            formKey: 'snippets',
            locales: [],
            routerAttributesToBackView: {0: 'webspace', 'id': 'active'},
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('test_route', {active: 4, locale: 'de', webspace: 'sulu_io'});
});

test('Should navigate to defined route on back button click without locale', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.backButton.onClick();
    expect(router.restore).toHaveBeenCalledWith('test_route', {});
});

test('Should navigate to defined route after dialog has been confirmed', async() => {
    const user = userEvent.setup();
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    mockFormContainerProps.store.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    const backView = {
        name: 'test_route',
    };
    const backViewAttributes = {};

    expect(queryDialog('sulu_admin.dirty_warning_dialog_title')).not.toBeInTheDocument();
    act(() => {
        expect(checkFormStoreDirtyStateBeforeNavigation({}, backViewAttributes, router.navigate)).toEqual(false);
    });
    expect(getDialog('sulu_admin.dirty_warning_dialog_title')).toBeInTheDocument();

    await clickDialogButton(
        user,
        'sulu_admin.dirty_warning_dialog_title',
        'sulu_admin.cancel'
    );
    expectDialogClosed('sulu_admin.dirty_warning_dialog_title');
    expect(router.navigate).not.toHaveBeenCalled();

    act(() => {
        mockFormContainerProps.store.dirty = true;
        expect(checkFormStoreDirtyStateBeforeNavigation(backView, backViewAttributes, router.navigate)).toEqual(false);
    });
    await clickDialogButton(
        user,
        'sulu_admin.dirty_warning_dialog_title',
        'sulu_admin.confirm'
    );
    expect(router.navigate).toHaveBeenCalledWith('test_route', backViewAttributes);
});

test('Should not show dialog on navigation if another route has already been loaded', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backView: 'test_route',
            formKey: 'snippets',
            toolbarActions: [],
        },
    };
    const otherRoute = {
        options: {
            toolbarActions: [],
        },
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        navigate: jest.fn(),
        route: otherRoute,
    };
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    mockFormContainerProps.store.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    const backViewAttributes = {};

    expect(queryDialog('sulu_admin.dirty_warning_dialog_title')).not.toBeInTheDocument();
    expect(checkFormStoreDirtyStateBeforeNavigation({}, backViewAttributes, router.navigate)).toEqual(true);
});

test('Should navigate to defined route after dialog has been confirmed using restore', async() => {
    const user = userEvent.setup();
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    mockFormContainerProps.store.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    const backView = {
        name: 'test_route',
    };
    const backViewAttributes = {};

    expect(queryDialog('sulu_admin.dirty_warning_dialog_title')).not.toBeInTheDocument();
    act(() => {
        expect(checkFormStoreDirtyStateBeforeNavigation({}, backViewAttributes, router.restore)).toEqual(false);
    });
    expect(getDialog('sulu_admin.dirty_warning_dialog_title')).toBeInTheDocument();

    await clickDialogButton(
        user,
        'sulu_admin.dirty_warning_dialog_title',
        'sulu_admin.cancel'
    );
    expectDialogClosed('sulu_admin.dirty_warning_dialog_title');
    expect(router.restore).not.toHaveBeenCalled();

    act(() => {
        mockFormContainerProps.store.dirty = true;
        expect(checkFormStoreDirtyStateBeforeNavigation(backView, backViewAttributes, router.restore)).toEqual(false);
    });
    await clickDialogButton(
        user,
        'sulu_admin.dirty_warning_dialog_title',
        'sulu_admin.confirm'
    );
    expect(router.restore).toHaveBeenCalledWith('test_route', backViewAttributes);
});

test('Should not close the window if formStore is still dirty', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    mockFormContainerProps.store.dirty = true;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    expect(queryDialog('sulu_admin.dirty_warning_dialog_title')).not.toBeInTheDocument();
    expect(checkFormStoreDirtyStateBeforeNavigation()).toEqual(false);
});

test('Should close the window if formStore is not dirty', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    mockFormContainerProps.store.dirty = false;

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    expectDialogClosed('sulu_admin.dirty_warning_dialog_title');
    expect(checkFormStoreDirtyStateBeforeNavigation()).toEqual(true);
});

test('Should not render back button when no editLink is configured', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    expect(toolbarConfig.backButton).toBe(undefined);
});

test('Should change locale by route navigation via locale chooser', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box()});

    const route = {
        name: 'sulu_admin.form',
        options: {
            backView: 'test_route',
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    act(() => resourceStore.locale.set('de'));

    const toolbarConfig = getToolbarConfig();
    toolbarConfig.locale.onChange('en');
    expect(router.navigate).toHaveBeenCalledWith('sulu_admin.form', {locale: 'en'});
});

test('Should show locales from router options in toolbar', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form locales={[]} resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should show locales from props in toolbar if route has no locales', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(
        <Form locales={['en', 'de']} resourceStore={resourceStore} route={route} router={router} />
    );

    const toolbarConfig = getToolbarConfig();
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should not show a locale chooser if no locales are passed in router options', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const toolbarConfig = getToolbarConfig();
    expect(toolbarConfig.locale).toBe(undefined);
});

test('Should initialize the ResourceStore with a schema', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);
    const metadataStore = require('../../../containers/Form/stores/metadataStore');

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

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);
    const schemaPromise = Promise.resolve({
        title: {},
        slogan: {},
    });
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

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
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainer.submit({action: 'publish'});
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put).toHaveBeenCalledWith(
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
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            routerAttributesToFormRequest: observable(['parentId', 'webspace']),
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainer.submit();
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put)
            .toHaveBeenCalledWith(
                'snippets',
                {value: 'Value'},
                {id: 8, locale: 'en', parentId: 3, webspace: 'sulu_io'}
            );
    });
});

test('Should save form when submitted with given requestParameters', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            requestParameters: {apiKey: 'api-option-value'},
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainer.submit();
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put)
            .toHaveBeenCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en', apiKey: 'api-option-value'});
    });
});

test('Should save form when submitted with mapped router attributes and given requestParameters', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            requestParameters: {apiKey: 'api-option-value'},
            routerAttributesToFormRequest: {'parentId': 'id', '0': 'webspace', 1: 'title'},
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainer.submit();
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put).toHaveBeenCalledWith(
            'snippets',
            {value: 'Value'},
            {id: 8, locale: 'en', apiKey: 'api-option-value', webspace: 'sulu_io', title: 'Sulu is awesome'}
        );
    });
});

test('Should save form when submitted with mapped named router attributes and given requestParameters', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            requestParameters: {apiKey: 'api-option-value'},
            routerAttributesToFormRequest: {'id': 'parentId'},
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainer.submit();
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put).toHaveBeenCalledWith(
            'snippets', {value: 'Value'}, {id: 8, locale: 'en', apiKey: 'api-option-value', parentId: 8}
        );
    });
});

test('Should show warning when form is submitted but already changed on the server and cancel', async() => {
    const user = userEvent.setup();
    const ResourceRequester = require('../../../services/ResourceRequester');
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(Promise.resolve({code: 1102}))});
    ResourceRequester.put.mockReturnValue(putPromise);
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    await Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]);

    mockFormContainer.submit({action: 'publish'});
    expect(resourceStore.destroy).not.toHaveBeenCalled();
    expect(ResourceRequester.put).toHaveBeenCalledWith(
        'snippets',
        {value: 'Value'},
        {action: 'publish', id: 8, locale: 'en'}
    );

    ResourceRequester.put.mockClear();

    expectDialogClosed('sulu_admin.has_changed_warning_dialog_title');

    await putPromise.catch(() => undefined);
    await waitFor(() => expect(getDialog('sulu_admin.has_changed_warning_dialog_title')).toHaveClass('open'));

    await clickDialogButton(
        user,
        'sulu_admin.has_changed_warning_dialog_title',
        'sulu_admin.cancel'
    );

    expectDialogClosed('sulu_admin.has_changed_warning_dialog_title');
    expect(ResourceRequester.put).not.toHaveBeenCalled();
});

test('Should show warning when form is submitted but already changed on the server and confirm', async() => {
    const user = userEvent.setup();
    const ResourceRequester = require('../../../services/ResourceRequester');
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(Promise.resolve({code: 1102}))});
    ResourceRequester.put.mockReturnValue(putPromise);
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    await Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]);

    mockFormContainer.submit({action: 'publish'});
    expect(resourceStore.destroy).not.toHaveBeenCalled();
    expect(ResourceRequester.put).toHaveBeenCalledWith(
        'snippets',
        {value: 'Value'},
        {action: 'publish', id: 8, locale: 'en'}
    );

    ResourceRequester.put.mockClear();

    expect(queryDialog('sulu_admin.has_changed_warning_dialog_title')).not.toBeInTheDocument();

    await putPromise.catch(() => undefined);
    await waitFor(() => expect(getDialog('sulu_admin.has_changed_warning_dialog_title')).toHaveClass('open'));

    ResourceRequester.put.mockReturnValueOnce(Promise.resolve({}));
    await clickDialogButton(
        user,
        'sulu_admin.has_changed_warning_dialog_title',
        'sulu_admin.confirm'
    );

    expectDialogClosed('sulu_admin.has_changed_warning_dialog_title');
    expect(ResourceRequester.put).toHaveBeenCalledWith(
        'snippets',
        {value: 'Value'},
        {action: 'publish', force: true, id: 8, locale: 'en'}
    );
});

test('Should set showSuccess flag after form submission', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    mockFormContainer.submit().then(() => {
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put).toHaveBeenCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en'});
        expect(getToolbarConfig().showSuccess.get()).toEqual(true);
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(getToolbarConfig().showSuccess.get()).toEqual(false);
    expect(mockFormContainerProps.onSuccess).toBeInstanceOf(Function);
    act(() => mockFormContainerProps.onSuccess());
    expect(getToolbarConfig().showSuccess.get()).toEqual(true);
});

test('Should show error if form has been tried to save although it is not valid', () => {
    const Form = require('../Form').default;
    const ResourceRequester = require('../../../services/ResourceRequester');
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({type: 'object', required: ['title']});
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return jsonSchemaPromise.then(() => {
            mockFormContainer.submit();
            expect(resourceStore.destroy).not.toHaveBeenCalled();
            expect(ResourceRequester.put).not.toHaveBeenCalled();
            expect(getToolbarConfig().errors).toEqual(['sulu_admin.form_contains_invalid_values']);
        });
    });
});

test('Should clear errors if form has been saved', () => {
    const Form = require('../Form').default;
    const ResourceRequester = require('../../../services/ResourceRequester');
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const putPromise = Promise.resolve({});
    ResourceRequester.put.mockReturnValue(putPromise);

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({type: 'object', required: []});
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();
    act(() => mockFormContainerProps.onError());
    expect(getToolbarConfig().errors).toHaveLength(1);

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return jsonSchemaPromise.then(() => {
            mockFormContainer.submit().then(() => {
                expect(ResourceRequester.put).toHaveBeenCalledWith('snippets', {}, {
                    action: undefined,
                    id: 8,
                    locale: 'en',
                });
                expect(getToolbarConfig().errors).toHaveLength(0);
            });
        });
    });
});

test('Should display generic error message if form submission fails', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const error = {code: 100, message: 'Something went wrong'};
    const errorPromise = Promise.resolve(error);
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(errorPromise)});
    ResourceRequester.put.mockReturnValue(putPromise);

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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    mockFormContainer.submit().then(() => {
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put).toHaveBeenCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en'});
        expect(getToolbarConfig().errors).toEqual(['sulu_admin.form_save_server_error']);
        done();
    });
});

test('Should display error message from server if server returns error message when submitting form', (done) => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const error = {code: 100, message: 'Something went wrong', detail: 'URL is already assigned to another page.'};
    const errorPromise = Promise.resolve(error);
    const putPromise = Promise.reject({json: jest.fn().mockReturnValue(errorPromise)});
    ResourceRequester.put.mockReturnValue(putPromise);

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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    mockFormContainer.submit().then(() => {
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(ResourceRequester.put).toHaveBeenCalledWith('snippets', {value: 'Value'}, {id: 8, locale: 'en'});
        expect(getToolbarConfig().errors).toEqual(['URL is already assigned to another page.']);
        done();
    });
});

test('Should save form when submitted and redirect to editView with mapped attributes', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets');

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            editView: 'editView',
            formKey: 'snippets',
            locales: [],
            routerAttributesToEditView: ['webspace'],
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return mockFormContainer.submit().then(() => {
            expect(resourceStore.destroy).toHaveBeenCalled();
            expect(ResourceRequester.post).toHaveBeenCalledWith('snippets', {value: 'Value'}, {});
            expect(router.navigate)
                .toHaveBeenCalledWith('editView', {id: undefined, locale: undefined, webspace: 'sulu_io'});
        });
    });
});

test('Should save form when submitted and redirect to editView', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve());
    ResourceRequester.post.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets');

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            editView: 'editView',
            formKey: 'snippets',
            locales: [],
            routerAttributesToEditView: {0: 'webspace', 'id': 'active'},
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        return mockFormContainer.submit().then(() => {
            expect(resourceStore.destroy).toHaveBeenCalled();
            expect(ResourceRequester.post).toHaveBeenCalledWith('snippets', {value: 'Value'}, {});
            expect(router.navigate)
                .toHaveBeenCalledWith('editView', {active: 8, id: undefined, locale: undefined, webspace: 'sulu_io'});
        });
    });
});

test('Should restore previous view to backView after MissingTypeOverlay has been cancelled', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const jsonSchemaPromise = Promise.resolve();
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const route = {
        options: {
            backView: 'sulu_snippet.snippet_list',
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        navigate: jest.fn(),
        restore: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    renderFormElement(
        <Form resourceStore={resourceStore} route={route} router={router} />
    );

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainerProps.onMissingTypeCancel();
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(router.restore).toHaveBeenCalledWith('sulu_snippet.snippet_list', {locale: 'en'});
    });
});

test('Should not restore previous view if no backView is given after MissingTypeOverlay has been cancelled', () => {
    const ResourceRequester = require('../../../services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const metadataStore = require('../../../containers/Form/stores/metadataStore');
    const resourceStore = new ResourceStore('snippets', 8, {locale: observable.box()});

    const schemaTypesPromise = Promise.resolve(null);
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
        restore: jest.fn(),
        route,
        attributes: {
            id: 8,
        },
    };
    renderFormElement(
        <Form resourceStore={resourceStore} route={route} router={router} />
    );

    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    return Promise.all([schemaTypesPromise, schemaPromise, jsonSchemaPromise]).then(() => {
        mockFormContainerProps.onMissingTypeCancel();
        expect(resourceStore.destroy).not.toHaveBeenCalled();
        expect(router.restore).not.toHaveBeenCalledWith();
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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(mockFormContainerProps.router).toEqual(router);
    expect(mockFormContainerProps.store.resourceStore).toEqual(resourceStore);
    expect(mockFormContainerProps.onSubmit).toBeInstanceOf(Function);
});

test('Should pass metadataRequestParameters options to Form View', () => {
    const metadataRequestParameters = {
        'testParam': 'testValue',
    };

    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            toolbarActions: [],
            metadataRequestParameters,
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        bind: jest.fn(),
        navigate: jest.fn(),
        route,
        attributes: {},
    };

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(mockFormContainerProps.store.resourceStore).toEqual(resourceStore);
    expect(mockFormContainerProps.store.metadataOptions).toEqual(metadataRequestParameters);
});

test('Should pass option to form metadata with routerAttribuesToFormMetadata', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});
    const metadataStore = require('../../../containers/Form/stores/metadataStore');

    const route = {
        options: {
            formKey: 'snippets',
            locales: [],
            routerAttributesToFormMetadata: ['webspace'],
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(metadataStore.getSchemaTypes).toHaveBeenCalledWith('snippets', {webspace: 'sulu_io'});
});

test('Should pass options to Form metadata with mixed routerAttribuesToFormMetadata', () => {
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippet', 1, {locale: observable.box('de')});
    const metadataStore = require('../../../containers/Form/stores/metadataStore');

    const route = {
        options: {
            backView: 'test_route',
            formKey: 'snippets',
            locales: [],
            routerAttributesToFormMetadata: {0: 'webspace', 'id': 'active'},
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
    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(metadataStore.getSchemaTypes).toHaveBeenCalledWith('snippets', {active: 4, webspace: 'sulu_io'});
});

test('Should destroy the store on unmount', () => {
    const formToolbarActionRegistry = require('../registries/formToolbarActionRegistry');
    const Form = require('../Form').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12, {locale: observable.box()});
    resourceStore.destroy = jest.fn();
    const saveToolbarActionDestroy = jest.fn();
    const deleteToolbarActionDestroy = jest.fn();

    const SaveToolbarAction = jest.fn(function() {
        this.getNode = jest.fn();
        this.destroy = saveToolbarActionDestroy;
    });

    const DeleteToolbarAction = jest.fn(function() {
        this.getNode = jest.fn();
        this.destroy = deleteToolbarActionDestroy;
    });

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
            locales: [],
            resourceKey: 'snippets',
            toolbarActions: [
                {
                    type: 'save',
                },
                {
                    type: 'delete',
                },
            ],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        bind: jest.fn(),
        route,
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const {unmount} = renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const locale = mockFormContainerProps.store.locale;

    expect(router.bind).toHaveBeenCalledWith('locale', locale);

    const resourceFormStore = mockFormContainerProps.store;
    resourceFormStore.destroy = jest.fn();

    unmount();
    expect(resourceFormStore.destroy).toHaveBeenCalled();
    expect(resourceStore.destroy).not.toHaveBeenCalled();
    expect(saveToolbarActionDestroy).toHaveBeenCalled();
    expect(deleteToolbarActionDestroy).toHaveBeenCalled();
});

test('Should destroy the own resourceStore if existing on unmount', () => {
    const Form = require('../Form').default;
    const CollaborationStore = require('../../../stores/CollaborationStore').default;
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 11);
    const ResourceRequester = require('../../../services/ResourceRequester');
    resourceStore.destroy = jest.fn();
    ResourceRequester.put.mockReturnValue(Promise.resolve({_embedded: {collaborations: []}}));
    const collaborationStoreDestroySpy = jest.spyOn(CollaborationStore.prototype, 'destroy');

    const route = {
        options: {
            formKey: 'snippets',
            resourceKey: 'pages',
            toolbarActions: [],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 12,
        },
        route,
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const {unmount} = renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);
    const formResourceStore = mockFormContainerProps.store.resourceStore;
    formResourceStore.destroy = jest.fn();

    unmount();
    expect(resourceStore.destroy).not.toHaveBeenCalled();
    expect(formResourceStore.destroy).toHaveBeenCalled();
    expect(collaborationStoreDestroySpy).toHaveBeenCalled();
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

    renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    expect(router.bind).not.toHaveBeenCalled();
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
    const {unmount} = renderFormElement(<Form resourceStore={resourceStore} route={route} router={router} />);

    const checkFormStoreDirtyStateBeforeNavigation = router.addUpdateRouteHook.mock.calls[0][0];

    expect(router.addUpdateRouteHook).toHaveBeenCalledWith(checkFormStoreDirtyStateBeforeNavigation, 2048);
    expect(checkFormStoreDirtyStateBeforeNavigationDisposerSpy).not.toHaveBeenCalledWith();

    unmount();
    expect(checkFormStoreDirtyStateBeforeNavigationDisposerSpy).toHaveBeenCalledWith();
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
    expect(() => renderFormElement(<Form router={router} />)).toThrow(/"ResourceTabs"/);
});

test('Should throw an error if no formKey is passed', () => {
    const ResourceStore = require('../../../stores/ResourceStore').default;
    const resourceStore = new ResourceStore('snippets', 12);

    const route = {
        options: {
            toolbarActions: [],
        },
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {},
        route,
    };
    const Form = require('../Form').default;
    expect(() => renderFormElement(
        <Form resourceStore={resourceStore} route={route} router={router} />
    )).toThrow(/"formKey"/);
});
