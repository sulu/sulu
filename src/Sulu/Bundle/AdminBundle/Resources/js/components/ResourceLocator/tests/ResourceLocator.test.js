// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import ResourceLocator from '../ResourceLocator';

test('ResourceLocator should render with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    expect(render(<ResourceLocator value={value} mode="full" onChange={onChange} onBlur={jest.fn()} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type full and a value of undefined', () => {
    const onChange = jest.fn();
    expect(render(<ResourceLocator value={undefined} mode="full" onChange={onChange} onBlur={jest.fn()} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    expect(render(<ResourceLocator value={value} mode="leaf" onChange={onChange} onBlur={jest.fn()} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type leaf and a value of undefined', () => {
    const onChange = jest.fn();
    expect(render(<ResourceLocator value={undefined} mode="leaf" onChange={onChange} onBlur={jest.fn()} />))
        .toMatchSnapshot();
});

test('ResourceLocator should call the onChange callback when the input changes with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const resourceLocator = mount(
        <ResourceLocator value={value} mode="full" onChange={onChange} onBlur={jest.fn()} />
    );
    resourceLocator.find('Input').props().onChange('parent-new');
    expect(onChange).toHaveBeenCalledWith('/parent-new');
});

test('ResourceLocator should call the onChange callback when the input changes with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const resourceLocator = mount(
        <ResourceLocator value={value} mode="leaf" onChange={onChange} onBlur={jest.fn()} />
    );
    resourceLocator.find('Input').props().onChange('child-new');
    expect(onChange).toHaveBeenCalledWith('/parent/child-new');
});

test('ResourceLocator should call the onChange callback with undefined if no input is given', () => {
    const onChange = jest.fn();
    const resourceLocator = mount(<ResourceLocator value="/url" mode="leaf" onChange={onChange} />);
    resourceLocator.find('Input').prop('onChange')(undefined);
    expect(onChange).toHaveBeenCalledWith(undefined);
});

test('ResourceLocator should call the onBlur callback when the Input finishes editing', () => {
    const finishSpy = jest.fn();

    const resourceLocator = shallow(
        <ResourceLocator value="/some/url" mode="leaf" onChange={jest.fn()} onBlur={finishSpy} />
    );

    resourceLocator.find('Input').simulate('blur');

    expect(finishSpy).toBeCalledWith();
});
