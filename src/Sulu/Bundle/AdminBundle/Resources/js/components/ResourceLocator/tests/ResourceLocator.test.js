// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import ResourceLocator from '../ResourceLocator';

test('ResourceLocator should render with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    expect(render(<ResourceLocator value={value} mode="full" onChange={onChange} onFinish={jest.fn()} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    expect(render(<ResourceLocator value={value} mode="leaf" onChange={onChange} onFinish={jest.fn()} />))
        .toMatchSnapshot();
});

test('ResourceLocator should call the onChange callback when the input changes with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const resourceLocator = mount(
        <ResourceLocator value={value} mode="full" onChange={onChange} onFinish={jest.fn()} />
    );
    resourceLocator.find('Input').props().onChange('parent-new');
    expect(onChange).toHaveBeenCalledWith('/parent-new');
});

test('ResourceLocator should call the onChange callback when the input changes with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const resourceLocator = mount(
        <ResourceLocator value={value} mode="leaf" onChange={onChange} onFinish={jest.fn()} />
    );
    resourceLocator.find('Input').props().onChange('child-new');
    expect(onChange).toHaveBeenCalledWith('/parent/child-new');
});

test('ResourceLocator should call the onFinish callback when the Input finishes editing', () => {
    const finishSpy = jest.fn();

    const resourceLocator = mount(
        <ResourceLocator value="/some/url" mode="leaf" onChange={jest.fn()} onFinish={finishSpy} />
    );

    resourceLocator.find('Input').prop('onFinish')();

    expect(finishSpy).toBeCalledWith();
});
