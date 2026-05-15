// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Requester from '../../../../services/Requester';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';
import ResourceLocatorHistory from '../../../../containers/ResourceLocatorHistory';
import Button from '../../../../components/Button';
import userStore from '../../../../stores/userStore';

jest.mock('../../../../stores/userStore', () => ({}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = observableOptions.locale;

    mockExtendObservable(this, {
        data: {},
    });
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.options = options || {};
    this.resourceStore = resourceStore;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.resourceKey = formStore.resourceKey;
    this.errors = {};
    this.addFinishFieldHandler = jest.fn();
    this.getPathsByTag = jest.fn().mockReturnValue([]);
    this.getValueByPath = jest.fn((path) => formStore.resourceStore.data[path]);
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({});
    this.isFieldModified = jest.fn().mockReturnValue(false);
}));

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

jest.mock('../../../../components/ResourceLocator', () => jest.fn(() => null));
jest.mock('../../../../containers/ResourceLocatorHistory', () => jest.fn(() => null));
jest.mock('../../../../components/Button', () => jest.fn(() => null));

const ResourceLocatorComponentMock = (ResourceLocatorComponent: any);
const ResourceLocatorHistoryMock = (ResourceLocatorHistory: any);
const ButtonMock = (Button: any);

const getMockCallProps = (mockComponent) => mockComponent.mock.calls.map(([props]) => props);

const getLastMockCallProps = (mockComponent) => {
    const props = getMockCallProps(mockComponent);
    if (props.length === 0) {
        throw new Error('Expected mock component to be called');
    }

    return props[props.length - 1];
};

const getResourceLocatorProps = () => getLastMockCallProps(ResourceLocatorComponentMock);
const getResourceLocatorHistoryProps = () => getLastMockCallProps(ResourceLocatorHistoryMock);
const getRefreshButtonProps = () => getLastMockCallProps(ButtonMock);

const renderResourceLocator = (customProps: Object = {}) => {
    const view = render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            {...customProps}
        />
    );

    return view;
};

const waitForPromise = async(promise: Promise<mixed>) => {
    await act(async() => {
        await promise;
    });
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass props correctly to ResourceLocator', async() => {
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

    const modePromise = Promise.resolve('full');

    const resourceLocator = renderResourceLocator({
        disabled: true,
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        value: '/url',
    });

    await waitForPromise(modePromise);

    expect(getResourceLocatorProps().value).toBe('/url');
    expect(getResourceLocatorProps().mode).toBe('full');
    expect(getResourceLocatorProps().disabled).toBe(true);
    expect(getResourceLocatorProps().locale.get()).toBe('en');

    // should not throw any error on unmount
    resourceLocator.unmount();
});

test('Render just slash instead of ResourceLocatorComponent if used on the homepage', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const modePromise = Promise.resolve('leaf');

    const resourceLocator = renderResourceLocator({
        disabled: true,
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        value: '/',
    });

    await waitForPromise(modePromise);

    expect(ResourceLocatorComponentMock).not.toBeCalled();
    expect(resourceLocator.container).toHaveTextContent('/');

    // should not throw any error on unmount
    resourceLocator.unmount();
});

test('Pass correct options to ResourceLocatorHistory if entity already existed', async() => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 1), 'test', {webspace: 'sulu'})
    );

    const modePromise = Promise.resolve('leaf');

    renderResourceLocator({
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
            options: {history: true},
        },
        formInspector,
        schemaOptions: {
            entity_class: {name: 'entity_class', value: 'entity-class-value'},
        },
    });

    await waitForPromise(modePromise);

    expect(ResourceLocatorHistoryMock).toHaveBeenCalledTimes(1);
    expect(getResourceLocatorHistoryProps().options)
        .toEqual({entityClass: 'entity-class-value', history: true, webspace: 'sulu', resourceKey: 'test'});
    expect(getResourceLocatorHistoryProps().resourceKey).toEqual('page_resourcelocators');
    expect(getResourceLocatorHistoryProps().id).toEqual(1);
});

test('Pass locale from userStore to ResourceLocator and ResourceLocatorHistory if form has no locale', async() => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', 1, {'locale': undefined}),
            'test',
            {webspace: 'sulu'}
        )
    );

    const modePromise = Promise.resolve('full');

    // $FlowFixMe
    userStore.contentLocale = 'cz';

    renderResourceLocator({
        disabled: true,
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
            options: {history: true},
        },
        formInspector,
    });

    await waitForPromise(modePromise);

    expect(getResourceLocatorProps().locale.get()).toBe('cz');
    expect(getResourceLocatorHistoryProps().options.locale).toBe('cz');
});

test('Do not add an addFinishFieldHandler for URL generation if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    renderResourceLocator({
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
        value: '/',
    });

    expect(formInspector.addFinishFieldHandler).not.toBeCalled();
});

test('Do not add an addFinishFieldHandler for URL generation if no generationUrl was passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    renderResourceLocator({
        fieldTypeOptions: {
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
    });

    expect(formInspector.addFinishFieldHandler).not.toBeCalled();
});

test.each(['leaf', 'full'])('Set mode correctly', async(mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const modePromise = Promise.resolve(mode);

    renderResourceLocator({
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        value: '/test/xxx',
    });

    await waitForPromise(modePromise);

    expect(getResourceLocatorProps().mode).toBe(mode);
});

test('Should fire onFinish callback without argument when ResourceLocatorComponent is blurred', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    const modePromise = Promise.resolve('leaf');

    renderResourceLocator({
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        onFinish: finishSpy,
    });

    await waitForPromise(modePromise);

    act(() => {
        getResourceLocatorProps().onBlur('Test');
    });

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

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
        onChange: changeSpy,
        schemaPath: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {
            locale: 'en',
            resourceKey: 'tests',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
        }
    );

    await waitForPromise(resourceLocatorPromise);

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

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
            resourceStorePropertiesToRequest: {
                propertyName: 'requestParamKey',
            },
        },
        formInspector,
        onChange: changeSpy,
        schemaOptions: {
            entity_class: {name: 'entity_class', value: 'entity-class-value'},
            route_schema: {name: 'entity_class', value: '/events/{implode("-", object)}'},
        },
        schemaPath: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {
            locale: 'en',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            entityClass: 'entity-class-value',
            routeSchema: '/events/{implode("-", object)}',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    await waitForPromise(resourceLocatorPromise);

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

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
        schemaPath: '/url',
        value: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(Requester.post).not.toBeCalled();
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

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
        schemaPath: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    resourceStore.data = {
        '/title': '',
        '/subtitle': undefined,
    };

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request new URL when part field is finished if input was already changed manually', async() => {
    const resourceStore = new ResourceStore('tests', undefined, {locale: observable.box('en')});
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        schemaPath: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    await waitForPromise(modePromise);

    act(() => {
        getResourceLocatorProps().onChange('manual-change');
    });

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
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

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
        schemaPath: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'other-tag'},
        ],
    });

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(Requester.post).not.toBeCalled();
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

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => Promise.resolve('leaf'),
        },
        formInspector,
        schemaPath: '/url',
    });

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({});

    act(() => {
        finishFieldHandler('/block/0/title', '/title');
    });

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/title');
    expect(Requester.post).not.toBeCalled();
});

test('Should enable refresh button when value of part field changes on edit form', async() => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        schemaPath: '/url',
    });

    await waitForPromise(modePromise);

    expect(getRefreshButtonProps().disabled).toBeTruthy();

    act(() => {
        resourceStore.data['/title'] = 'new-title-value';
    });

    expect(getRefreshButtonProps().disabled).toBeFalsy();
});

test('Should enable refresh button when input is changed manually on edit form', async() => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        schemaPath: '/url',
    });

    await waitForPromise(modePromise);

    expect(getRefreshButtonProps().disabled).toBeTruthy();

    act(() => {
        getResourceLocatorProps().onChange('manual-change');
    });

    expect(getRefreshButtonProps().disabled).toBeFalsy();
});

test('Should not enable refresh button when value of part field changes on add form', async() => {
    const resourceStore = new ResourceStore('tests', undefined);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        schemaPath: '/url',
    });

    await waitForPromise(modePromise);

    expect(getRefreshButtonProps().disabled).toBeTruthy();

    act(() => {
        resourceStore.data['/title'] = 'new-title-value';
    });

    expect(getRefreshButtonProps().disabled).toBeTruthy();
});

test('Should enable refresh button when input is changed manually on add form', async() => {
    const resourceStore = new ResourceStore('tests', undefined);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        schemaPath: '/url',
    });

    await waitForPromise(modePromise);

    expect(getRefreshButtonProps().disabled).toBeTruthy();

    act(() => {
        getResourceLocatorProps().onChange('manual-change');
    });

    expect(getRefreshButtonProps().disabled).toBeFalsy();
});

test('Should not enable refresh button when value of part field changes if all parts are empty', async() => {
    const resourceStore = new ResourceStore('tests', 5);
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore,
            'test'
        )
    );
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
        },
        formInspector,
        schemaPath: '/url',
    });

    await waitForPromise(modePromise);

    expect(getRefreshButtonProps().disabled).toBeTruthy();

    act(() => {
        resourceStore.data['/title'] = '';
        resourceStore.data['/subtitle'] = undefined;
    });

    expect(getRefreshButtonProps().disabled).toBeTruthy();

    act(() => {
        getResourceLocatorProps().onChange('manual-change');
    });

    expect(getRefreshButtonProps().disabled).toBeTruthy();
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
    const modePromise = Promise.resolve('leaf');

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    resourceStore.data = {
        '/title': 'title-value',
        '/subtitle': 'subtitle-value',
        '/propertyName': 'property-value',
    };

    renderResourceLocator({
        dataPath: '/block/0/url',
        fieldTypeOptions: {
            generationUrl: '/admin/api/resourcelocators?action=generate',
            historyResourceKey: 'page_resourcelocators',
            modeResolver: () => modePromise,
            resourceStorePropertiesToRequest: {
                propertyName: 'requestParamKey',
            },
        },
        formInspector,
        onChange: changeSpy,
        schemaPath: '/url',
    });

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    await waitForPromise(modePromise);

    act(() => {
        getResourceLocatorProps().onChange('manual-change');
    });

    expect(getRefreshButtonProps().disabled).toBeFalsy();

    act(() => {
        getRefreshButtonProps().onClick();
    });

    expect(getRefreshButtonProps().disabled).toBeTruthy();
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {
            id: 5,
            locale: 'en',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    await waitForPromise(resourceLocatorPromise);

    expect(changeSpy).toBeCalledWith('/test');
});
