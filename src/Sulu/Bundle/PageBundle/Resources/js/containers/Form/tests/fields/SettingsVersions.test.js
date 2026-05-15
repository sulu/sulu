// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import {Dialog} from 'sulu-admin-bundle/components';
import {FormInspector, List, ListStore, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Route, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SettingsVersions from '../../fields/SettingsVersions';

jest.mock('sulu-admin-bundle/components', () => ({
    Dialog: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(function(resourceFormStore) {
        this.options = resourceFormStore.options;
        this.locale = resourceFormStore.locale;
        this.id = resourceFormStore.id;
        this.addSaveHandler = jest.fn();
    }),
    List: jest.fn(() => null),
    ListStore: jest.fn(function() {
        this.reload = jest.fn();
    }),
    ResourceFormStore: jest.fn(function(resourceStore) {
        this.options = resourceStore.options;
        this.locale = resourceStore.locale;
        this.id = resourceStore.id;
    }),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock(
    'sulu-admin-bundle/stores/ResourceStore',
    () => jest.fn(function(resourceKey, id, observableOptions, options) {
        this.options = options;
        this.locale = observableOptions.locale;
        this.id = id;
    })
);

function createFormInspector(locale: Object = observable.box('en'), options: Object = {webspace: 'sulu'}) {
    return new FormInspector(
        new ResourceFormStore(
            new ResourceStore('pages', 3, {locale}, options),
            'test'
        )
    );
}

function createSchemaOptions(overrides: Object = {}) {
    return {
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
        ...overrides,
    };
}

function renderSettingsVersions(props: Object = {}) {
    return render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            schemaOptions={createSchemaOptions()}
            {...props}
        />
    );
}

function getLatestListProps() {
    const calls = ((List: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestDialogProps() {
    const calls = ((Dialog: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function expectRenderToThrow(renderFn: () => void, message: string) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    expect(renderFn).toThrow(message);
    consoleErrorSpy.mockRestore();
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Initialize the list correctly', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);
    const schemaOptions = createSchemaOptions();

    renderSettingsVersions({formInspector, schemaOptions});

    expect(ListStore).toBeCalledWith(
        'page_versions',
        'page_versions',
        'page_versions',
        expect.objectContaining({locale}),
        {id: 3, webspace: 'sulu'}
    );

    expect(getLatestListProps()).toEqual(expect.objectContaining({
        adapters: ['table'],
        searchable: false,
        selectable: false,
        // $FlowFixMe
        store: ListStore.mock.instances[0],
    }));
});

test('Reload the ListStore if a new version was published', () => {
    const formInspector = createFormInspector();
    const schemaOptions = createSchemaOptions();

    renderSettingsVersions({formInspector, schemaOptions});

    // $FlowFixMe
    const listStore = ListStore.mock.instances[0];
    const saveHandler = formInspector.addSaveHandler.mock.calls[0][0];
    saveHandler('publish');

    expect(listStore.reload).toBeCalledWith();
});

test('Do not reload the ListStore if page was saved without being published', () => {
    const formInspector = createFormInspector();
    const schemaOptions = createSchemaOptions();

    renderSettingsVersions({formInspector, schemaOptions});

    // $FlowFixMe
    const listStore = ListStore.mock.instances[0];
    const saveHandler = formInspector.addSaveHandler.mock.calls[0][0];
    saveHandler('draft');

    expect(listStore.reload).not.toBeCalled();
});

test('Open and cancel restore overlay', () => {
    const formInspector = createFormInspector();
    const schemaOptions = createSchemaOptions();

    renderSettingsVersions({formInspector, schemaOptions});

    expect(getLatestDialogProps().open).toEqual(false);

    getLatestListProps().itemActionsProvider()[0].onClick(3);

    expect(getLatestDialogProps().open).toEqual(true);

    getLatestDialogProps().onCancel();

    expect(getLatestDialogProps().open).toEqual(false);
});

test('Open and confirm restore overlay', async() => {
    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);
    const schemaOptions = createSchemaOptions();

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

    const postPromise = Promise.resolve();
    ResourceRequester.post.mockReturnValue(postPromise);

    renderSettingsVersions({
        formInspector,
        router,
        schemaOptions,
    });

    expect(getLatestDialogProps().open).toEqual(false);

    getLatestListProps().itemActionsProvider()[0].onClick(3);

    expect(getLatestDialogProps().open).toEqual(true);

    getLatestDialogProps().onConfirm();

    expect(getLatestDialogProps().confirmLoading).toEqual(true);

    await act(async() => {
        await postPromise;
    });

    expect(getLatestDialogProps().open).toEqual(false);
    expect(getLatestDialogProps().confirmLoading).toEqual(false);
    expect(router.navigate).toBeCalledWith('sulu_page.page_edit_form', {id: 3, locale, webspace: 'sulu'});
});

test('Throw error when resource_key parameter is undefined', () => {
    const formInspector = createFormInspector();
    const schemaOptions = {
        list_key: {
            name: 'list_key',
            value: 'page_versions',
        },
        user_settings_key: {
            name: 'user_settings_key',
            value: 'page_versions',
        },
    };

    expectRenderToThrow(
        () => renderSettingsVersions({formInspector, schemaOptions: (schemaOptions: any)}),
        'The "resource_key" schemaOption is mandatory and must be a string, but received undefined!'
    );
});

test('Throw error when resource_key parameter is not a string', () => {
    const formInspector = createFormInspector();

    const schemaOptions = createSchemaOptions({
        resource_key: {
            name: 'resource_key',
            value: 123,
        },
    });

    expectRenderToThrow(
        () => renderSettingsVersions({formInspector, schemaOptions: (schemaOptions: any)}),
        'The "resource_key" schemaOption is mandatory and must be a string, but received number!'
    );
});

test('Use resource_key as a fallback, if list_key parameter is undefined', () => {
    const formInspector = createFormInspector();

    const schemaOptions = {
        resource_key: {
            name: 'resource_key',
            value: 'page_versions_resource_key',
        },
        user_settings_key: {
            name: 'user_settings_key',
            value: 'page_versions',
        },
    };

    renderSettingsVersions({formInspector, schemaOptions});

    // $FlowFixMe
    expect(ListStore.mock.calls[0][1]).toBe('page_versions_resource_key');
});

test('Throw error when list_key parameter is not a string', () => {
    const formInspector = createFormInspector();

    const schemaOptions = createSchemaOptions({
        list_key: {
            name: 'list_key',
            value: 123,
        },
    });

    expectRenderToThrow(
        () => renderSettingsVersions({formInspector, schemaOptions: (schemaOptions: any)}),
        'The "list_key" schemaOption must be a string, but received number!'
    );
});

test('Use list_key as a fallback, if user_settings_key parameter is undefined', () => {
    const formInspector = createFormInspector();

    const schemaOptions = {
        resource_key: {
            name: 'resource_key',
            value: 'page_versions',
        },
        list_key: {
            name: 'list_key',
            value: 'page_versions_list_key',
        },
    };

    renderSettingsVersions({formInspector, schemaOptions});

    // $FlowFixMe
    expect(ListStore.mock.calls[0][2]).toBe('page_versions_list_key');
});

test('Throw error when user_settings_key parameter is not a string.', () => {
    const formInspector = createFormInspector();

    const schemaOptions = createSchemaOptions({
        user_settings_key: {
            name: 'user_settings_key',
            value: 123,
        },
    });

    expectRenderToThrow(
        () => renderSettingsVersions({formInspector, schemaOptions: (schemaOptions: any)}),
        'The "user_settings_key" schemaOption must be a string, but received number!'
    );
});

test('Throw error when no parent route is set', () => {
    const formInspector = createFormInspector();
    const schemaOptions = createSchemaOptions();
    const router = new Router();

    const settingsVersions = new SettingsVersions({
        ...fieldTypeDefaultProps,
        formInspector,
        router,
        schemaOptions,
    });

    expect(
        () => settingsVersions.parentRoute
    ).toThrow('A route with a valid parent route is required for this field type to work properly!');
});
