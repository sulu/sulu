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

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.id = id;
    this.locale = observableOptions.locale ? observableOptions.locale.get() : undefined;
}));

jest.mock('../../stores/ResourceFormStore', () => jest.fn(function(resourceStore, formKey, options) {
    this.id = resourceStore.id;
    this.locale = resourceStore.locale;
    this.options = options;
}));

jest.mock('../../FormInspector', () => jest.fn(function(formStore) {
    this.id = formStore.id;
    this.locale = formStore.locale;
    this.options = formStore.options;
    this.errors = {};
    this.addFinishFieldHandler = jest.fn();
    this.getValuesByTag = jest.fn();
    this.getSchemaEntryByPath = jest.fn().mockReturnValue({});
    this.isFieldModified = jest.fn().mockReturnValue(false);
}));

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

test('Pass props correctly to ResourceLocator', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        mode: {
            value: 'full',
        },
    };

    const resourceLocator = shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="/"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('value')).toBe('/');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('full');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('disabled')).toBe(true);
});

test('Throw an exception if a non-valid mode is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        mode: {
            value: 'test',
        },
    };

    expect(
        () => shallow(
            <ResourceLocator
                {...fieldTypeDefaultProps}
                fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )
    ).toThrow(/"leaf" or "full"/);
});

test('Throw an exception if a no generationUrl is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    expect(
        () => shallow(
            <ResourceLocator
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
            />
        )
    ).toThrow(/"generationUrl"/);
});

test('Set default mode correctly', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            value="/test/xxx"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('leaf');
});

test('Should not pass any argument to onFinish callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();

    const resourceLocator = mount(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    resourceLocator.find(ResourceLocatorComponent).prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});

test('Should not request a new URL if on an edit form', () =>{
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test', 1), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
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
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
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
    expect(formInspector.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {locale: 'en', parts: ['te', 'st']}
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
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
            value="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).not.toBeCalled();
    expect(formInspector.getValuesByTag).not.toBeCalled();
    expect(Requester.post).not.toBeCalled();
});

test('Should request a new URL including the options from the ResourceFormStore if no URL was defined', () => {
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
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
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
    expect(formInspector.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).toBeCalledWith(
        '/admin/api/resourcelocators?action=generate',
        {locale: undefined, parts: ['te', 'st'], webspace: 'example'}
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
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
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
    formInspector.getValuesByTag.mockReturnValue([]);
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if only empty parts are available', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue([null, undefined]);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if a field without the "sulu.rlp.part" tag has finished editing', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp'},
        ],
    });
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if a field without any tags has finished editing', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    shallow(
        <ResourceLocator
            {...fieldTypeDefaultProps}
            dataPath="/block/0/url"
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
    finishFieldHandler('/block/0/url', '/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).not.toBeCalledWith('sulu.rlp.part');
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
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath="/url"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
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
    expect(formInspector.getValuesByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalledWith();
});
