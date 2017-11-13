// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import ResourceLocator from '../ResourceLocator';

test('ResourceLocator should render with type full', () => {
    const onChange = () => {};
    const value = '/parent';
    expect(render(<ResourceLocator value={value} mode="full" onChange={onChange} />)).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const onChange = () => {};
    const value = '/parent/child';
    expect(render(<ResourceLocator value={value} mode="leaf" onChange={onChange} />)).toMatchSnapshot();
});

test('ResourceLocator should call the callback when the input changes with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const resourceLocator = mount(<ResourceLocator value={value} mode="full" onChange={onChange} />);
    resourceLocator.find('Input').props().onChange('parent-new');
    expect(onChange).toHaveBeenCalledWith('/parent-new');
});

test('ResourceLocator should call the callback when the input changes with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const resourceLocator = mount(<ResourceLocator value={value} mode="leaf" onChange={onChange} />);
    resourceLocator.find('Input').props().onChange('child-new');
    expect(onChange).toHaveBeenCalledWith('/parent/child-new');
});
