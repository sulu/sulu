// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Requester from '../../../../services/Requester';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceLocator from '../../fields/ResourceLocator';
import userStore from '../../../../stores/userStore';

let mockResourceLocatorProps: Object = {};
let mockResourceLocatorHistoryProps: Object = {};
let mockButtonProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../utils/Translator');

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

jest.mock('../../../../components/ResourceLocator', () => jest.fn((props) => {
    mockResourceLocatorProps = props;

    return mockReact.createElement('input', {
        disabled: props.disabled,
        onBlur: props.onBlur,
        onChange: (event) => props.onChange(event.target.value),
        value: props.value || '',
    });
}));

jest.mock('../../../../components/Button', () => jest.fn((props) => {
    mockButtonProps = props;

    return mockReact.createElement(
        'button',
        {
            disabled: props.disabled,
            onClick: props.onClick,
            type: 'button',
        },
        props.children
    );
}));

jest.mock('../../../ResourceLocatorHistory', () => jest.fn((props) => {
    mockResourceLocatorHistoryProps = props;

    return mockReact.createElement('div');
}));

beforeEach(() => {
    mockResourceLocatorProps = {};
    mockResourceLocatorHistoryProps = {};
    mockButtonProps = {};
    Requester.post.mockReset();
    // $FlowFixMe
    userStore.contentLocale = undefined;
});

const createPendingPromise = () => new Promise(() => {});

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

    const {unmount} = render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            value="/url"
        />
    );

    await waitFor(() => expect(mockResourceLocatorProps.mode).toBe('full'));

    expect(mockResourceLocatorProps.value).toBe('/url');
    expect(mockResourceLocatorProps.disabled).toBe(true);
    expect(mockResourceLocatorProps.locale.get()).toBe('en');

    expect(() => unmount()).not.toThrow();
});

test('Render just slash instead of ResourceLocatorComponent if used on the homepage', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const modePromise = Promise.resolve('leaf');

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    expect(await screen.findByText('/')).toBeInTheDocument();
    expect(mockResourceLocatorProps).toEqual({});
});

test('Pass correct options to ResourceLocatorHistory if entity already existed', async() => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 1), 'test', {webspace: 'sulu'})
    );

    const modePromise = Promise.resolve('leaf');

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
                options: {history: true},
            }}
            formInspector={formInspector}
            schemaOptions={{
                entity_class: {name: 'entity_class', value: 'entity-class-value'},
            }}
        />
    );

    await waitFor(() => expect(mockResourceLocatorHistoryProps.resourceKey).toBe('page_resourcelocators'));

    expect(mockResourceLocatorHistoryProps.options)
        .toEqual({entityClass: 'entity-class-value', history: true, webspace: 'sulu', resourceKey: 'test'});
    expect(mockResourceLocatorHistoryProps.id).toEqual(1);
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
                options: {history: true},
            }}
            formInspector={formInspector}
        />
    );

    await waitFor(() => expect(mockResourceLocatorProps.mode).toBe('full'));

    expect(mockResourceLocatorProps.locale.get()).toBe('cz');
    expect(mockResourceLocatorHistoryProps.options.locale).toBe('cz');
});

test('Do not add an addFinishFieldHandler for URL generation if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toHaveBeenCalled();
});

test('Do not add an addFinishFieldHandler for URL generation if no generationUrl was passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toHaveBeenCalled();
});

test.each(['leaf', 'full'])('Set mode correctly', async(mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const modePromise = Promise.resolve(mode);

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            value="/test/xxx"
        />
    );

    await waitFor(() => expect(mockResourceLocatorProps.mode).toBe(mode));
});

test('Should fire onFinish callback without argument when ResourceLocatorComponent is blurred', async() => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    const modePromise = Promise.resolve('leaf');

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    await waitFor(() => expect(mockResourceLocatorProps.mode).toBe('leaf'));

    mockResourceLocatorProps.onBlur('Test');

    expect(finishSpy).toHaveBeenCalledWith();
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
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

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

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).toHaveBeenCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {
            locale: 'en',
            resourceKey: 'tests',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
        }
    );

    await resourceLocatorPromise;
    expect(changeSpy).toHaveBeenCalledWith('/test');
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
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
                resourceStorePropertiesToRequest: {
                    propertyName: 'requestParamKey',
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{
                entity_class: {name: 'entity_class', value: 'entity-class-value'},
                route_schema: {name: 'entity_class', value: '/events/{implode("-", object)}'},
            }}
            schemaPath="/url"
        />
    );

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

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).toHaveBeenCalledWith(
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

    await resourceLocatorPromise;
    expect(changeSpy).toHaveBeenCalledWith('/test');
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
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
            value="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    finishFieldHandler('/block/0/title', '/title');
    expect(Requester.post).not.toHaveBeenCalled();
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
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

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

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toHaveBeenCalled();
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    await waitFor(() => expect(mockResourceLocatorProps.mode).toBe('leaf'));

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    mockResourceLocatorProps.onChange('manual-change');

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toHaveBeenCalled();
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
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'other-tag'},
        ],
    });

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(Requester.post).not.toHaveBeenCalled();
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
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: createPendingPromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({});

    finishFieldHandler('/block/0/title', '/title');

    expect(formInspector.getSchemaEntryByPath).toHaveBeenCalledWith('/title');
    expect(Requester.post).not.toHaveBeenCalled();
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    await waitFor(() => expect(mockButtonProps.disabled).toBe(true));

    resourceStore.data['/title'] = 'new-title-value';

    await waitFor(() => expect(mockButtonProps.disabled).toBe(false));
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    await waitFor(() => expect(mockButtonProps.disabled).toBe(true));

    mockResourceLocatorProps.onChange('manual-change');

    await waitFor(() => expect(mockButtonProps.disabled).toBe(false));
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    await waitFor(() => expect(mockButtonProps.disabled).toBe(true));

    resourceStore.data['/title'] = 'new-title-value';

    expect(mockButtonProps.disabled).toBe(true);
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    await waitFor(() => expect(mockButtonProps.disabled).toBe(true));

    mockResourceLocatorProps.onChange('manual-change');

    await waitFor(() => expect(mockButtonProps.disabled).toBe(false));
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    await waitFor(() => expect(mockButtonProps.disabled).toBe(true));

    resourceStore.data['/title'] = '';
    resourceStore.data['/subtitle'] = undefined;

    expect(mockButtonProps.disabled).toBe(true);

    mockResourceLocatorProps.onChange('manual-change');

    expect(mockButtonProps.disabled).toBe(true);
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

    render(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
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
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    await waitFor(() => expect(mockButtonProps.disabled).toBe(true));

    mockResourceLocatorProps.onChange('manual-change');
    await waitFor(() => expect(mockButtonProps.disabled).toBe(false));

    mockButtonProps.onClick();

    expect(mockButtonProps.disabled).toBe(true);
    expect(formInspector.getPathsByTag).toHaveBeenCalledWith('sulu.rlp.part');
    expect(Requester.post).toHaveBeenCalledWith(
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

    await resourceLocatorPromise;
    expect(changeSpy).toHaveBeenCalledWith('/test');
});
