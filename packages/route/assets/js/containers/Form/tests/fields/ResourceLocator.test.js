// @flow
import React from 'react';
import {act, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import fieldTypeDefaultProps from 'sulu-admin-bundle/utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from 'sulu-admin-bundle/containers/Form/FormInspector';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import Requester from 'sulu-admin-bundle/services/Requester';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import userStore from 'sulu-admin-bundle/stores/userStore';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';
import ResourceLocatorHistory from '../../../../containers/ResourceLocatorHistory';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = observableOptions.locale;

    mockExtendObservable(this, {
        data: {},
        dirty: false,
    });
}));

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options) {
        this.resourceKey = resourceStore.resourceKey;
        this.id = resourceStore.id;
        this.locale = resourceStore.locale;
        this.options = options || {};
        this.resourceStore = resourceStore;
        this.dirty = resourceStore.dirty;
    })
);

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.resourceKey = formStore.resourceKey;
    this.formStore = formStore;
    this.errors = {};
    this.addFinishFieldHandler = jest.fn(() => jest.fn());
    this.getPathsByTag = jest.fn().mockReturnValue([]);
    this.getValueByPath = jest.fn((path) => formStore.resourceStore.data[path]);
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({});
    this.isFieldModified = jest.fn().mockReturnValue(false);
}));

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../components/ResourceLocator', () => {
    const ResourceLocatorComponentMock: any = jest.fn(function ResourceLocatorComponentMock({value}: any) {
        return <div data-testid="resource-locator-component">{value}</div>;
    });

    return ResourceLocatorComponentMock;
});

jest.mock('../../../../containers/ResourceLocatorHistory', () => {
    const ResourceLocatorHistoryMock: any = jest.fn(function ResourceLocatorHistoryMock() {
        return <div data-testid="resource-locator-history" />;
    });

    return ResourceLocatorHistoryMock;
});

const ResourceLocatorComponentMock: any = ResourceLocatorComponent;
const ResourceLocatorHistoryMock: any = ResourceLocatorHistory;
const RequesterMock: any = Requester;
const userStoreMock: any = userStore;

function getRefreshButton() {
    return screen.getByRole('button', {name: /sulu_admin\.refresh_url/});
}

beforeEach(() => {
    jest.clearAllMocks();
    userStoreMock.contentLocale = undefined;
});

test('Pass props correctly to ResourceLocator', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore(
                'test',
                undefined,
                {'locale': observable.box('en')}
            ),
            'test'
        )
    );

    const {unmount} = render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_full_edit',
            }}
            formInspector={formInspector}
            value="/url"
        />
    );

    expect(getLatestMockProps(ResourceLocatorComponentMock).value).toBe('/url');
    expect(getLatestMockProps(ResourceLocatorComponentMock).mode).toBe('tree_full_edit');
    expect(getLatestMockProps(ResourceLocatorComponentMock).disabled).toBe(true);
    expect(getLatestMockProps(ResourceLocatorComponentMock).locale.get()).toBe('en');

    // should not throw any error on unmount
    unmount();
});

test('Render just slash instead of ResourceLocatorComponent if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const {container, unmount} = render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    expect(ResourceLocatorComponentMock).not.toBeCalled();
    expect(container).toHaveTextContent('/');

    // should not throw any error on unmount
    unmount();
});

test('Pass correct options to ResourceLocatorHistory if resource already existed', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 1), 'test', {webspace: 'sulu'})
    );

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                historyResourceKey: 'route-histories',
                defaultMode: 'tree_leaf_edit',
                options: {history: true},
            }}
            formInspector={formInspector}
            schemaOptions={{}}
        />
    );

    expect(ResourceLocatorHistoryMock).toHaveBeenCalledTimes(1);
    expect(getLatestMockProps(ResourceLocatorHistoryMock).options)
        .toEqual({history: true, webspace: 'sulu', resourceId: 1, resourceKey: 'test'});
    expect(getLatestMockProps(ResourceLocatorHistoryMock).resourceKey).toEqual('route-histories');
    expect(getLatestMockProps(ResourceLocatorHistoryMock).disabled).toEqual(false);
});

test('Pass locale from userStore to ResourceLocator and ResourceLocatorHistory if form has no locale', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', 1, {'locale': undefined}),
            'test',
            {webspace: 'sulu'}
        )
    );

    userStoreMock.contentLocale = 'cz';

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                historyResourceKey: 'route-histories',
                defaultMode: 'tree_full_edit',
                options: {history: true},
            }}
            formInspector={formInspector}
        />
    );

    expect(getLatestMockProps(ResourceLocatorComponentMock).locale.get()).toBe('cz');
    expect(getLatestMockProps(ResourceLocatorHistoryMock).options.locale).toBe('cz');
});

test('Do not add an addFinishFieldHandler for URL generation if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toBeCalled();
});

test('Do not add an addFinishFieldHandler for URL generation if no generationUrl was passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toBeCalled();
});

test.each(['tree_leaf_edit', 'tree_full_edit'])('Set mode correctly from fieldTypeOptions', (mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: mode,
            }}
            formInspector={formInspector}
            value="/test/xxx"
        />
    );

    expect(getLatestMockProps(ResourceLocatorComponentMock).mode).toBe(mode);
});

test.each(['tree_leaf_edit', 'tree_full_edit'])('Set mode correctly from schemaOptions', (mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                // invert to be sure that schemaOptions are used
                defaultMode: (mode === 'tree_leaf_edit' ? 'tree_full_edit' : 'tree_leaf_edit'),
            }}
            formInspector={formInspector}
            schemaOptions={{
                mode: {
                    name: 'mode',
                    value: mode,
                },
            }}
            value="/test/xxx"
        />
    );

    expect(getLatestMockProps(ResourceLocatorComponentMock).mode).toBe(mode);
});

test('Should fire onFinish callback without argument when ResourceLocatorComponent is blurred', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    getLatestMockProps(ResourceLocatorComponentMock).onBlur('Test');

    expect(finishSpy).toBeCalledWith();
});

test('Should automatically request new URL when part field is finished on add form', async() => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const changeSpy = jest.fn();

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourceLocator: '/test',
    });
    RequesterMock.post.mockReturnValue(resourceLocatorPromise);

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(RequesterMock.post).toBeCalledWith(
        '/admin/api/resource-locators',
        {
            locale: 'en',
            resourceKey: 'tests',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
        }
    );

    await resourceLocatorPromise;
    expect(changeSpy).toBeCalledWith('/test');
});

test('Should request URL with parameters from FormInspector options, fieldTypeOptions and schemaOptions', async() => {
    const resourceStore = new ResourceStore('test', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test',
            {webspace: 'example'}
        )
    );
    const changeSpy = jest.fn();

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
        '/propertyName': 'property-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
                resourceStorePropertiesToRequest: {
                    propertyName: 'requestParamKey',
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{
                route_schema: {name: 'route_schema', value: '/events/{implode("-", object)}'},
            }}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourceLocator: '/test',
    });
    RequesterMock.post.mockReturnValue(resourceLocatorPromise);

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(RequesterMock.post).toBeCalledWith(
        '/admin/api/resource-locators',
        {
            locale: 'en',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            routeSchema: '/events/{implode("-", object)}',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    await resourceLocatorPromise;
    expect(changeSpy).toBeCalledWith('/test');
});

test('Should not request new URL when part field is finished on edit form', () => {
    const resourceStore = new ResourceStore('test', 5, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
            value="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    finishFieldHandler('/block/0/title', '/title');
    expect(RequesterMock.post).not.toBeCalled();
});

test('Should not request new URL when part field is finished if all parts are empty', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    resourceStore.data = {
        '/title': '',
        '/subtitle': undefined,
    };

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(RequesterMock.post).not.toBeCalled();
});

test('Should not request new URL when part field is finished if input was already changed manually', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    act(() => {
        getLatestMockProps(ResourceLocatorComponentMock).onChange('manual-change');
    });

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(RequesterMock.post).not.toBeCalled();
});

test('Should not request new URL when field without the "sulu.rlp.part" tag is finished', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'other-tag'},
        ],
    });

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(RequesterMock.post).not.toBeCalled();
});

test('Should not request new URL when field without any tags has finished editing', () => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = getLatestMockProps(formInspector.addFinishFieldHandler);

    formInspector.getSchemaEntryByPath.mockReturnValue({});

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(RequesterMock.post).not.toBeCalled();
});

test('Should enable refresh button when value of part field changes on edit form', async() => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    expect(getRefreshButton()).toBeDisabled();

    act(() => {
        resourceStore.data['/title'] = 'new-title-value';
    });

    await waitFor(() => {
        expect(getRefreshButton()).toBeEnabled();
    });
});

test('Should enable refresh button when input is changed manually on edit form', () => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    expect(getRefreshButton()).toBeDisabled();

    act(() => {
        getLatestMockProps(ResourceLocatorComponentMock).onChange('manual-change');
    });

    expect(getRefreshButton()).toBeEnabled();
});

test('Should not enable refresh button when value of part field changes on add form', () => {
    const resourceStore = new ResourceStore('tests', undefined);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    expect(getRefreshButton()).toBeDisabled();

    resourceStore.data['/title'] = 'new-title-value';

    expect(getRefreshButton()).toBeDisabled();
});

test('Should enable refresh button when input is changed manually on add form', () => {
    const resourceStore = new ResourceStore('tests', undefined);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    expect(getRefreshButton()).toBeDisabled();

    act(() => {
        getLatestMockProps(ResourceLocatorComponentMock).onChange('manual-change');
    });

    expect(getRefreshButton()).toBeEnabled();
});

test('Should not enable refresh button when value of part field changes if all parts are empty', () => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    expect(getRefreshButton()).toBeDisabled();

    resourceStore.data['/title'] = '';
    resourceStore.data['/subtitle'] = undefined;

    expect(getRefreshButton()).toBeDisabled();

    act(() => {
        getLatestMockProps(ResourceLocatorComponentMock).onChange('manual-change');
    });

    expect(getRefreshButton()).toBeDisabled();
});

test('Should request new URL with correct options and disable button when refresh button is clicked', async() => {
    const resourceStore = new ResourceStore('test', 5, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test',
            {webspace: 'example'}
        )
    );
    const changeSpy = jest.fn();

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
        '/propertyName': 'property-value',
    };

    const user = userEvent.setup();

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resource-locators',
                defaultMode: 'tree_leaf_edit',
                resourceStorePropertiesToRequest: {
                    propertyName: 'requestParamKey',
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );
    const resourceLocatorPromise = Promise.resolve({
        resourceLocator: '/test',
    });
    RequesterMock.post.mockReturnValue(resourceLocatorPromise);

    act(() => {
        getLatestMockProps(ResourceLocatorComponentMock).onChange('manual-change');
    });
    expect(getRefreshButton()).toBeEnabled();

    await user.click(getRefreshButton());

    expect(getRefreshButton()).toBeDisabled();
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(RequesterMock.post).toBeCalledWith(
        '/admin/api/resource-locators',
        {
            resourceId: 5,
            locale: 'en',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    await resourceLocatorPromise;
    expect(changeSpy).toBeCalledWith('/test');
});
