// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import {FormInspector, ListStore, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Route, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SettingsVersions from '../../fields/SettingsVersions';

let mockDialogProps: Object = {};
let mockListProps: Object = {};

const mockReact = require('react');

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/components', () => ({
    Dialog: jest.fn((props) => {
        mockDialogProps = props;

        return mockReact.createElement(
            'div',
            {
                'data-open': String(props.open),
                'data-testid': 'dialog',
            },
            props.open && mockReact.createElement(
                mockReact.Fragment,
                {},
                mockReact.createElement('h1', {}, props.title),
                mockReact.createElement('div', {}, props.children),
                mockReact.createElement(
                    'button',
                    {onClick: () => props.onConfirm(), type: 'button'},
                    props.confirmText
                ),
                mockReact.createElement('button', {onClick: () => props.onCancel(), type: 'button'}, props.cancelText)
            )
        );
    }),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(resourceFormStore) {
        this.options = resourceFormStore.options;
        this.locale = resourceFormStore.locale;
        this.id = resourceFormStore.id;
        this.addSaveHandler = jest.fn();
    }),
    List: jest.fn((props) => {
        mockListProps = props;

        const itemActions = props.itemActionsProvider ? props.itemActionsProvider() : [];

        return mockReact.createElement(
            'div',
            {'data-testid': 'list'},
            itemActions.map((itemAction) => mockReact.createElement(
                'button',
                {
                    key: itemAction.icon,
                    onClick: () => itemAction.onClick(3),
                    type: 'button',
                },
                itemAction.icon
            ))
        );
    }),
    ListStore: jest.fn(function() {
        this.reload = jest.fn();
    }),
    ResourceFormStore: jest.fn(function(resourceStore) {
        this.options = resourceStore.options;
        this.locale = resourceStore.locale;
        this.id = resourceStore.id;
    }),
}));

jest.mock('sulu-admin-bundle/services', () => {
    const actual = jest.requireActual('sulu-admin-bundle/services');

    return {
        ...actual,
        ResourceRequester: {
            post: jest.fn(),
        },
        Router: jest.fn(function() {
            this.navigate = jest.fn();
        }),
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id, observableOptions = {}, options = {}) {
        this.options = options;
        this.locale = observableOptions.locale;
        this.id = id;
    }),
}));

const schemaOptions = {
    resource_key: {
        name: 'resource_key',
        value: 'page_versions',
    },
    list_key: {
        name: 'list_key',
        value: 'page_versions',
    },
    user_settings_key: {
        name: 'user_settings_key',
        value: 'page_versions',
    },
};

function createFormInspector(locale = observable.box('en')) {
    return new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
            'test'
        )
    );
}

function createSettingsVersions(props: Object = {}) {
    return new SettingsVersions({
        ...fieldTypeDefaultProps,
        formInspector: createFormInspector(),
        schemaOptions,
        ...props,
    });
}

beforeEach(() => {
    mockDialogProps = {};
    mockListProps = {};

    (ListStore: any).mockClear();
    ResourceRequester.post.mockReset();
});

test('Initialize the list correctly', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    render(<SettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />);

    expect(ListStore).toHaveBeenCalledWith(
        'page_versions',
        'page_versions',
        'page_versions',
        expect.objectContaining({locale}),
        {id: 3, webspace: 'sulu'}
    );

    expect(mockListProps).toEqual(expect.objectContaining({
        adapters: ['table'],
        searchable: false,
        selectable: false,
        store: (ListStore: any).mock.instances[0],
    }));
});

test('Reload the ListStore if a new version was published', () => {
    const formInspector = createFormInspector();

    render(<SettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />);

    const listStore = (ListStore: any).mock.instances[0];
    const saveHandler = formInspector.addSaveHandler.mock.calls[0][0];
    saveHandler('publish');

    expect(listStore.reload).toHaveBeenCalledWith();
});

test('Do not reload the ListStore if page was saved without being published', () => {
    const formInspector = createFormInspector();

    render(<SettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />);

    const listStore = (ListStore: any).mock.instances[0];
    const saveHandler = formInspector.addSaveHandler.mock.calls[0][0];
    saveHandler('draft');

    expect(listStore.reload).not.toHaveBeenCalled();
});

test('Open and cancel restore overlay', async() => {
    const user = userEvent.setup();
    const formInspector = createFormInspector();

    render(<SettingsVersions {...fieldTypeDefaultProps} formInspector={formInspector} schemaOptions={schemaOptions} />);

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');

    await user.click(screen.getByRole('button', {name: 'su-process'}));

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');
});

test('Open and confirm restore overlay', async() => {
    const user = userEvent.setup();
    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    const route = new Route({
        name: 'sulu_page.page_edit_form.settings',
        path: '/settings',
        type: 'test',
    });

    route.parent = new Route({
        name: 'sulu_page.page_edit_form',
        path: '/details',
        type: 'test',
    });

    const router = new Router();
    router.route = route;

    let resolvePost;
    const postPromise = new Promise((resolve) => {
        resolvePost = resolve;
    });
    ResourceRequester.post.mockReturnValue(postPromise);

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            schemaOptions={schemaOptions}
        />
    );

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'false');

    await user.click(screen.getByRole('button', {name: 'su-process'}));

    expect(screen.getByTestId('dialog')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(ResourceRequester.post).toHaveBeenCalledWith(
        'page_versions',
        {},
        {action: 'restore', id: 3, version: 3, locale, webspace: 'sulu'}
    );
    expect(mockDialogProps.confirmLoading).toEqual(true);

    await act(async() => {
        resolvePost();
        await postPromise;
    });

    expect(mockDialogProps.open).toEqual(false);
    expect(mockDialogProps.confirmLoading).toEqual(false);
    expect(router.navigate).toHaveBeenCalledWith('sulu_page.page_edit_form', {id: 3, locale, webspace: 'sulu'});
});

test('Throw error when resource_key parameter is undefined', () => {
    expect(() => createSettingsVersions({
        schemaOptions: {
            list_key: {
                name: 'list_key',
                value: 'page_versions',
            },
            user_settings_key: {
                name: 'user_settings_key',
                value: 'page_versions',
            },
        },
    })).toThrow('The "resource_key" schemaOption is mandatory and must be a string, but received undefined!');
});

test('Throw error when resource_key parameter is not a string', () => {
    expect(() => createSettingsVersions({
        schemaOptions: {
            resource_key: {
                name: 'resource_key',
                value: 123,
            },
            list_key: {
                name: 'list_key',
                value: 'page_versions',
            },
            user_settings_key: {
                name: 'user_settings_key',
                value: 'page_versions',
            },
        },
    })).toThrow('The "resource_key" schemaOption is mandatory and must be a string, but received number!');
});

test('Use resource_key as a fallback, if list_key parameter is undefined', () => {
    createSettingsVersions({
        schemaOptions: {
            resource_key: {
                name: 'resource_key',
                value: 'page_versions_resource_key',
            },
            user_settings_key: {
                name: 'user_settings_key',
                value: 'page_versions',
            },
        },
    });

    expect(ListStore).toHaveBeenCalledWith(
        'page_versions_resource_key',
        'page_versions_resource_key',
        'page_versions',
        expect.any(Object),
        {id: 3, webspace: 'sulu'}
    );
});

test('Throw error when list_key parameter is not a string', () => {
    expect(() => createSettingsVersions({
        schemaOptions: {
            resource_key: {
                name: 'resource_key',
                value: 'page_versions',
            },
            list_key: {
                name: 'list_key',
                value: 123,
            },
            user_settings_key: {
                name: 'user_settings_key',
                value: 'page_versions',
            },
        },
    })).toThrow('The "list_key" schemaOption must be a string, but received number!');
});

test('Use list_key as a fallback, if user_settings_key parameter is undefined', () => {
    createSettingsVersions({
        schemaOptions: {
            resource_key: {
                name: 'resource_key',
                value: 'page_versions',
            },
            list_key: {
                name: 'list_key',
                value: 'page_versions_list_key',
            },
        },
    });

    expect(ListStore).toHaveBeenCalledWith(
        'page_versions',
        'page_versions_list_key',
        'page_versions_list_key',
        expect.any(Object),
        {id: 3, webspace: 'sulu'}
    );
});

test('Throw error when user_settings_key parameter is not a string.', () => {
    expect(() => createSettingsVersions({
        schemaOptions: {
            resource_key: {
                name: 'resource_key',
                value: 'page_versions',
            },
            list_key: {
                name: 'list_key',
                value: 'page_versions',
            },
            user_settings_key: {
                name: 'user_settings_key',
                value: 123,
            },
        },
    })).toThrow('The "user_settings_key" schemaOption must be a string, but received number!');
});

test('Throw error when no parent route is set', () => {
    const router = new Router();
    const pageSettingsVersions = createSettingsVersions({router});

    expect(
        () => pageSettingsVersions.parentRoute
    ).toThrow('A route with a valid parent route is required for this field type to work properly!');
});
