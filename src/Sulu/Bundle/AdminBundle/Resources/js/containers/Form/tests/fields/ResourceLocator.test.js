// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';

test('Pass props correctly to ResourceLocator', () => {
    const schemaOptions = {
        mode: {
            value: 'full',
        },
    };

    const resourceLocator = shallow(
        <ResourceLocator
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            value="/"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('value')).toBe('/');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('full');
});

test('Throw an exception if a non-valid mode is passed', () => {
    const schemaOptions = {
        mode: {
            value: 'test',
        },
    };

    expect(
        () => shallow(<ResourceLocator onChange={jest.fn()} schemaOptions={schemaOptions} schemaPath="" value="/" />)
    ).toThrow(/"leaf" or "full"/);
});

test('Set default value correctly with undefined value', () => {
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath=""
            value={undefined}
        />
    );

    expect(changeSpy).toBeCalledWith('/');
});

test('Set default value correctly with empty string', () => {
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaPath=""
            value=''
        />
    );

    expect(changeSpy).toBeCalledWith('/');
});

test('Set default mode correctly', () => {
    const resourceLocator = mount(
        <ResourceLocator
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            value={'/test/xxx'}
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('leaf');
});

test('Should not pass any argument to onFinish callback', () => {
    const finishSpy = jest.fn();

    const resourceLocator = mount(
        <ResourceLocator
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaPath=""
            value={'/test/xxx'}
        />
    );

    resourceLocator.find(ResourceLocatorComponent).prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});

