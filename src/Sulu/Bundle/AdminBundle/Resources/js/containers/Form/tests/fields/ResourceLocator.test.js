// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Requester from '../../../../services/Requester';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = observableOptions.locale;
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options) {
    this.resourceKey = resourceStore.resourceKey;
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.options = options;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.resourceKey = formStore.resourceKey;
    this.errors = {};
    this.addFinishFieldHandler = jest.fn();
    this.getPathsByTag = jest.fn();
    this.getValueByPath = jest.fn();
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({});
    this.isFieldModified = jest.fn().mockReturnValue(false);
}));

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

test('Pass props correctly to ResourceLocator', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const modePromise = Promise.resolve('full');

    const resourceLocator = shallow(
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

    return modePromise.then(() => {
        expect(resourceLocator.find(ResourceLocatorComponent).prop('value')).toBe('/url');
        expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('full');
        expect(resourceLocator.find(ResourceLocatorComponent).prop('disabled')).toBe(true);
    });
});

test('Render just slash instead of ResourceLocatorComponent if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const modePromise = Promise.resolve('leaf');

    const resourceLocator = shallow(
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

    return modePromise.then(() => {
        resourceLocator.update();
        expect(resourceLocator.find(ResourceLocatorComponent)).toHaveLength(0);
        expect(resourceLocator.text()).toEqual('/');
    });
});

test('Do not render history link if new entity is created', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const modePromise = Promise.resolve('leaf');

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
            }}
            formInspector={formInspector}
        />
    );

    return modePromise.then(() => {
        resourceLocator.update();
        expect(resourceLocator.find('ResourceLocatorHistory')).toHaveLength(0);
    });
});

test('Render history link if entity already existed including passed options', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test', 1), 'test', {webspace: 'sulu'})
    );

    const modePromise = Promise.resolve('leaf');

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => modePromise,
                options: {history: true},
            }}
            formInspector={formInspector}
        />
    );

    return modePromise.then(() => {
        resourceLocator.update();
        expect(resourceLocator.find('ResourceLocatorHistory')).toHaveLength(1);
        expect(resourceLocator.find('ResourceLocatorHistory').prop('options'))
            .toEqual({history: true, webspace: 'sulu', resourceKey: 'test'});
        expect(resourceLocator.find('ResourceLocatorHistory').prop('resourceKey')).toEqual('page_resourcelocators');
        expect(resourceLocator.find('ResourceLocatorHistory').prop('id')).toEqual(1);
    });
});

test('Do not add an addFinishFieldHandler for URL generation if no generationUrl was passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toBeCalled();
});

test('Do not add an addFinishFieldHandler for URL generation if used on the homepage', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            value="/"
        />
    );

    expect(formInspector.addFinishFieldHandler).not.toBeCalled();
});

test.each(['leaf', 'full'])('Set mode correctly', (mode) => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const modePromise = Promise.resolve(mode);

    const resourceLocator = mount(
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

    return modePromise.then(() => {
        resourceLocator.update();
        expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe(mode);
    });
});

test('Should not pass any argument to onFinish callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    const modePromise = Promise.resolve('leaf');

    const resourceLocator = mount(
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

    return modePromise.then(() => {
        resourceLocator.update();
        resourceLocator.find(ResourceLocatorComponent).prop('onBlur')('Test');

        expect(finishSpy).toBeCalledWith();
    });
});

test('Should not request a new URL if on an edit form', () =>{
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', 1),
            'test',
            {webspace: 'sulu'}
        )
    );
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    finishFieldHandler('/url');
    expect(Requester.post).not.toBeCalled();
});

test('Should request a new URL if no URL was defined', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('tests', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    formInspector.getValueByPath.mockReturnValueOnce('title-value');
    formInspector.getValueByPath.mockReturnValueOnce('subtitle-value');
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });
    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(formInspector.getValueByPath).toBeCalledWith('/title');
    expect(formInspector.getValueByPath).toBeCalledWith('/subtitle');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {
            locale: 'en',
            resourceKey: 'tests',
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
        }
    );

    return resourceLocatorPromise.then(() => {
        expect(changeSpy).toBeCalledWith('/test');
    });
});

test('Should not request a new URL if URL was defined', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            onChange={changeSpy}
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

    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).not.toBeCalled();
    expect(formInspector.getPathsByTag).not.toBeCalled();
    expect(Requester.post).not.toBeCalled();
});

test('Request new URL with options from FormInspector and resourceStorePropertiesToRequest field-type-option ', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'example'}
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
                resourceStorePropertiesToRequest: {
                    requestParamKey: 'propertyName',
                },
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    formInspector.getValueByPath.mockReturnValueOnce('title-value');
    formInspector.getValueByPath.mockReturnValueOnce('subtitle-value');
    formInspector.getValueByPath.mockReturnValueOnce('property-value');
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    const resourceLocatorPromise = Promise.resolve({
        resourcelocator: '/test',
    });
    Requester.post.mockReturnValue(resourceLocatorPromise);

    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(formInspector.getValueByPath).toBeCalledWith('/title');
    expect(formInspector.getValueByPath).toBeCalledWith('/subtitle');
    expect(formInspector.getValueByPath).toBeCalledWith('/propertyName');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {
            locale: undefined,
            parts: {title: 'title-value', subtitle: 'subtitle-value'},
            resourceKey: 'test',
            webspace: 'example',
            requestParamKey: 'property-value',
        }
    );

    return resourceLocatorPromise.then(() => {
        expect(changeSpy).toBeCalledWith('/test');
    });
});

test('Should not request a new URL if no parts are available', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
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
    formInspector.getPathsByTag.mockReturnValue([]);
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if only empty parts are available', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    formInspector.getValueByPath.mockReturnValueOnce(null);
    formInspector.getValueByPath.mockReturnValueOnce(undefined);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).toBeCalledWith('sulu.rlp.part');
    expect(formInspector.getValueByPath).toBeCalledWith('/title');
    expect(formInspector.getValueByPath).toBeCalledWith('/subtitle');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if a field without the "sulu.rlp.part" tag has finished editing', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    formInspector.getValueByPath.mockReturnValueOnce('title-value');
    formInspector.getValueByPath.mockReturnValueOnce('subtitle-value');
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp'},
        ],
    });
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if a field without any tags has finished editing', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    formInspector.getValueByPath.mockReturnValueOnce('title-value');
    formInspector.getValueByPath.mockReturnValueOnce('subtitle-value');
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if the resource locator field has already been edited', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test'),
            'test',
            {webspace: 'example'}
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{
                generationUrl: '/admin/api/resourcelocators?action=generate',
                historyResourceKey: 'page_resourcelocators',
                modeResolver: () => Promise.resolve('leaf'),
            }}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getPathsByTag.mockReturnValue(['/title', '/subtitle']);
    formInspector.getValueByPath.mockReturnValueOnce('title-value');
    formInspector.getValueByPath.mockReturnValueOnce('subtitle-value');
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });
    formInspector.isFieldModified.mockReturnValue(true);

    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.isFieldModified).toHaveBeenCalledTimes(1);
    expect(formInspector.isFieldModified).toBeCalledWith('/block/0/url');
    expect(formInspector.getSchemaEntryByPath).not.toBeCalledWith('/url');
    expect(formInspector.getPathsByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalledWith();
});
