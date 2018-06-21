// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Requester from '../../../../services/Requester';
import ResourceStore from '../../../../stores/ResourceStore';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions = {}) {
    this.id = id;
    this.locale = observableOptions.locale ? observableOptions.locale.get() : undefined;
}));

jest.mock('../../stores/FormStore', () => jest.fn(function(resourceStore, options) {
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
}));

jest.mock('../../../../services/Requester', () => ({
    post: jest.fn(),
}));

test('Pass props correctly to ResourceLocator', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        mode: {
            value: 'full',
        },
    };

    const resourceLocator = shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            value="/"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('value')).toBe('/');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('full');
});

test('Throw an exception if a non-valid mode is passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        mode: {
            value: 'test',
        },
    };

    expect(
        () => shallow(
            <ResourceLocator
                error={undefined}
                fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
                formInspector={formInspector}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                schemaOptions={schemaOptions}
                schemaPath=""
                showAllErrors={false}
                value="/"
            />
        )
    ).toThrow(/"leaf" or "full"/);
});

test('Throw an exception if a no generationUrl is passed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));

    expect(
        () => shallow(
            <ResourceLocator
                error={undefined}
                fieldTypeOptions={{}}
                formInspector={formInspector}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                schemaPath=""
                showAllErrors={false}
                value="/"
            />
        )
    ).toThrow(/"generationUrl"/);
});

test('Set default value correctly with undefined value', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            value={undefined}
        />
    );

    expect(changeSpy).toBeCalledWith('/');
});

test('Set default value correctly with empty string', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            value=""
        />
    );

    expect(changeSpy).toBeCalledWith('/');
});

test('Set default mode correctly', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const resourceLocator = mount(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('leaf');
});

test('Should not pass any argument to onFinish callback', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();

    const resourceLocator = mount(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaPath=""
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    resourceLocator.find(ResourceLocatorComponent).prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});

test('Should not request a new URL if on an edit form', () =>{
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test', 1)));
    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    finishFieldHandler('/url');
    expect(Requester.post).not.toBeCalled();
});

test('Should request a new URL if on an add form', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')})
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
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

    finishFieldHandler('/url');

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

test('Should request a new URL including the options from the FormStore if on an add form', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test'), {webspace: 'example'}));
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
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

    finishFieldHandler('/url');

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
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });
    formInspector.getValuesByTag.mockReturnValue([]);
    finishFieldHandler('/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if only empty parts are available', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue([null, undefined]);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });
    finishFieldHandler('/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if a field without the "sulu.rlp.part" tag has finished editing', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp'},
        ],
    });
    finishFieldHandler('/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if a field without any tags has finished editing', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
    finishFieldHandler('/url');

    expect(formInspector.getSchemaEntryByPath).toBeCalledWith('/url');
    expect(formInspector.getValuesByTag).not.toBeCalledWith('sulu.rlp.part');
    expect(Requester.post).not.toBeCalled();
});

test('Should not request a new URL if there is an error on the form', () => {
    const formInspector = new FormInspector(
        new FormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')})
        )
    );
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            error={undefined}
            fieldTypeOptions={{generationUrl: '/admin/api/resourcelocators?action=generate'}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath="/url"
            showAllErrors={false}
            value="/test/xxx"
        />
    );

    const finishFieldHandler = formInspector.addFinishFieldHandler.mock.calls[0][0];

    // $FlowFixMe
    formInspector.errors = {title: {}};
    formInspector.getValuesByTag.mockReturnValue(['te', 'st']);
    formInspector.getSchemaEntryByPath.mockReturnValue({
        tags: [
            {name: 'sulu.rlp.part'},
        ],
    });

    finishFieldHandler('/url');

    expect(formInspector.getSchemaEntryByPath).not.toBeCalled();
    expect(formInspector.getValuesByTag).not.toBeCalled();
    expect(Requester.post).not.toBeCalled();
});
