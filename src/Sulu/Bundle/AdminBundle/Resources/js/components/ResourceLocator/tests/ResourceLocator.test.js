// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import ResourceLocator from '../ResourceLocator';

test('ResourceLocator should render with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    expect(render(<ResourceLocator mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type full and a value of undefined', () => {
    const onChange = jest.fn();
    expect(render(<ResourceLocator mode="full" onBlur={jest.fn()} onChange={onChange} value={undefined} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    expect(render(<ResourceLocator mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render with type leaf and a value of undefined', () => {
    const onChange = jest.fn();
    expect(render(<ResourceLocator mode="leaf" onBlur={jest.fn()} onChange={onChange} value={undefined} />))
        .toMatchSnapshot();
});

test('ResourceLocator should render when disabled', () => {
    const onChange = jest.fn();
    const value = '/parent';
    expect(render(<ResourceLocator disabled={true} mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />))
        .toMatchSnapshot();
});

test('ResourceLocator should call the onChange callback when the input changes with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const resourceLocator = mount(
        <ResourceLocator mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('parent-new');
    expect(onChange).toHaveBeenCalledWith('/parent-new');
});

test('ResourceLocator should call the onChange callback when the input changes with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const resourceLocator = mount(
        <ResourceLocator mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('child-new');
    expect(onChange).toHaveBeenCalledWith('/parent/child-new');
});

test('ResourceLocator should not call the onChange callback when a slash is typed in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const resourceLocator = mount(
        <ResourceLocator mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('/parent/child/');
    expect(onChange).not.toBeCalled();
});

test('ResourceLocator should call the onChange callback when a slash is typed in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const resourceLocator = mount(
        <ResourceLocator mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('parent/child/');
    expect(onChange).toBeCalledWith('/parent/child/');
});

test('ResourceLocator should call the onChange callback with undefined if no input is given', () => {
    const onChange = jest.fn();
    const resourceLocator = mount(<ResourceLocator mode="leaf" onChange={onChange} value="/url" />);
    resourceLocator.find('Input').prop('onChange')(undefined);
    expect(onChange).toHaveBeenCalledWith(undefined);
});

test('ResourceLocator should call the onBlur callback when the Input finishes editing', () => {
    const finishSpy = jest.fn();

    const resourceLocator = shallow(
        <ResourceLocator mode="leaf" onBlur={finishSpy} onChange={jest.fn()} value="/some/url" />
    );

    resourceLocator.find('Input').simulate('blur');

    expect(finishSpy).toBeCalledWith();
});
