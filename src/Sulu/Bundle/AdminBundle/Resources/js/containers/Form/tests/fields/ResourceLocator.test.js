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
        () => shallow(<ResourceLocator onChange={jest.fn()} schemaOptions={schemaOptions} value="/" />)
    ).toThrow(/"leaf" or "full"/);
});

test('Set default value correctly with undefined value', () => {
    const changeSpy = jest.fn();

    shallow(
        <ResourceLocator
            onChange={changeSpy}
            onFinish={jest.fn()}
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
            value={'/test/xxx'}
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe('leaf');
});
