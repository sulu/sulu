// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import {ResourceRequester, Route, Router} from 'sulu-admin-bundle/services';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import {Dialog} from 'sulu-admin-bundle/components';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import {getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import SettingsVersions from '../../fields/SettingsVersions';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Dialog: jest.fn(() => null),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    List: jest.fn(() => null),
    ListStore: jest.fn(function() {
        this.reload = jest.fn();
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    post: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(resourceFormStore) {
    this.options = resourceFormStore.options;
    this.locale = resourceFormStore.locale;
    this.id = resourceFormStore.id;
    this.addSaveHandler = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.options = resourceStore.options;
    this.locale = resourceStore.locale;
    this.id = resourceStore.id;
}));

jest.mock(
    'sulu-admin-bundle/stores/ResourceStore',
    () => jest.fn(function(resourceKey, id, observableOptions, options) {
        this.options = options;
        this.locale = observableOptions.locale;
        this.id = id;
    })
);

const getDefaultSchemaOptions = (overrides = {}): any => ({
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
});

const createFormInspector = (locale = observable.box('en')) => new FormInspector(
    new ResourceFormStore(
        new ResourceStore('pages', 3, {locale}, {webspace: 'sulu'}),
        'test'
    )
);

const getLatestListProps = () => getLatestMockProps((List: any));
const getLatestDialogProps = () => getLatestMockProps((Dialog: any));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Initialize the list correctly', () => {
    const locale = observable.box('en');
    const formInspector = createFormInspector(locale);

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions()}
        />
    );

    expect(ListStore).toHaveBeenCalledWith(
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
        store: (ListStore: any).mock.instances[0],
    }));
});

test('Reload the ListStore if a new version was published', () => {
    const formInspector = createFormInspector();

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions()}
        />
    );

    const listStore = (ListStore: any).mock.instances[0];
    const saveHandler = getLatestMockProps(formInspector.addSaveHandler);

    saveHandler('publish');

    expect(listStore.reload).toHaveBeenCalled();
});

test('Do not reload the ListStore if page was saved without being published', () => {
    const formInspector = createFormInspector();

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions()}
        />
    );

    const listStore = (ListStore: any).mock.instances[0];
    const saveHandler = getLatestMockProps(formInspector.addSaveHandler);

    saveHandler('draft');

    expect(listStore.reload).not.toHaveBeenCalled();
});

test('Open and cancel restore overlay', () => {
    const formInspector = createFormInspector();

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions()}
        />
    );

    expect(getLatestDialogProps().open).toEqual(false);

    act(() => {
        getLatestListProps().itemActionsProvider()[0].onClick(3);
    });

    expect(getLatestDialogProps().open).toEqual(true);

    act(() => {
        getLatestDialogProps().onCancel();
    });

    expect(getLatestDialogProps().open).toEqual(false);
});

test('Open and confirm restore overlay', async() => {
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

    let resolvePostPromise = () => {};
    const postPromise = new Promise((resolve) => {
        resolvePostPromise = resolve;
    });
    ResourceRequester.post.mockReturnValue(postPromise);

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            schemaOptions={getDefaultSchemaOptions()}
        />
    );

    expect(getLatestDialogProps().open).toEqual(false);

    act(() => {
        getLatestListProps().itemActionsProvider()[0].onClick(3);
    });

    expect(getLatestDialogProps().open).toEqual(true);

    act(() => {
        getLatestDialogProps().onConfirm();
    });

    expect(getLatestDialogProps().confirmLoading).toEqual(true);

    await act(async() => {
        resolvePostPromise();
        await postPromise;
    });

    expect(getLatestDialogProps().open).toEqual(false);
    expect(getLatestDialogProps().confirmLoading).toEqual(false);
    expect(router.navigate).toHaveBeenCalledWith('sulu_page.page_edit_form', {id: 3, locale, webspace: 'sulu'});
});

test('Throw error when resource_key parameter is undefined', () => {
    const formInspector = createFormInspector();

    expect(() => render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions({resource_key: undefined})}
        />
    )).toThrow('The "resource_key" schemaOption is mandatory and must be a string, but received undefined!');
});

test('Throw error when resource_key parameter is not a string', () => {
    const formInspector = createFormInspector();

    expect(() => render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions({resource_key: {name: 'resource_key', value: 123}})}
        />
    )).toThrow('The "resource_key" schemaOption is mandatory and must be a string, but received number!');
});

test('Use resource_key as a fallback, if list_key parameter is undefined', () => {
    const formInspector = createFormInspector();

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions({
                resource_key: {name: 'resource_key', value: 'page_versions_resource_key'},
                list_key: undefined,
            })}
        />
    );

    expect(ListStore).toHaveBeenCalledWith(
        'page_versions_resource_key',
        'page_versions_resource_key',
        'page_versions',
        expect.anything(),
        expect.anything()
    );
});

test('Throw error when list_key parameter is not a string', () => {
    const formInspector = createFormInspector();

    expect(() => render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions({list_key: {name: 'list_key', value: 123}})}
        />
    )).toThrow('The "list_key" schemaOption must be a string, but received number!');
});

test('Use list_key as a fallback, if user_settings_key parameter is undefined', () => {
    const formInspector = createFormInspector();

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions({
                list_key: {name: 'list_key', value: 'page_versions_list_key'},
                user_settings_key: undefined,
            })}
        />
    );

    expect(ListStore).toHaveBeenCalledWith(
        'page_versions',
        'page_versions_list_key',
        'page_versions_list_key',
        expect.anything(),
        expect.anything()
    );
});

test('Throw error when user_settings_key parameter is not a string.', () => {
    const formInspector = createFormInspector();

    expect(() => render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={getDefaultSchemaOptions({user_settings_key: {name: 'user_settings_key', value: 123}})}
        />
    )).toThrow('The "user_settings_key" schemaOption must be a string, but received number!');
});

test('Throw error when no parent route is set', () => {
    const formInspector = createFormInspector();
    const router = new Router();

    ResourceRequester.post.mockReturnValue({
        then: (callback) => {
            callback();
            return Promise.resolve();
        },
    });

    render(
        <SettingsVersions
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
            schemaOptions={getDefaultSchemaOptions()}
        />
    );

    act(() => {
        getLatestListProps().itemActionsProvider()[0].onClick(3);
    });

    expect(() => {
        getLatestDialogProps().onConfirm();
    }).toThrow('A route with a valid parent route is required for this field type to work properly!');
});
