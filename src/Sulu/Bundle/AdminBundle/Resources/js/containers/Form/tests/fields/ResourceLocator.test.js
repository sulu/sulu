// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import ResourceLocator from '../../fields/ResourceLocator';
import ResourceLocatorComponent from '../../../../components/ResourceLocator';

test('Pass props correctly to ResourceLocator', () => {
    const options = {
        mode: 'full',
    };

    const resourceLocator = shallow(
        <ResourceLocator
            onChange={jest.fn()}
            onFinish={jest.fn()}
            options={options}
            value="/"
        />
    );

    expect(resourceLocator.find(ResourceLocatorComponent).prop('value')).toBe('/');
    expect(resourceLocator.find(ResourceLocatorComponent).prop('mode')).toBe(options.mode);
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
