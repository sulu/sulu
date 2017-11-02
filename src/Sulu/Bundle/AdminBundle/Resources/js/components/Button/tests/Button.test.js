/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Button from '../Button';

test('Button should render with type primary', () => {
    expect(render(<Button type="primary" />)).toMatchSnapshot();
});

test('Button should render with type secondary', () => {
    expect(render(<Button type="secondary" />)).toMatchSnapshot();
});

test('Button should call the callback on click', () => {
    const onClick = jest.fn();
    const button = shallow(<Button type="primary" onClick={onClick} />);
    button.find('button').simulate('click');
    expect(onClick).toBeCalled();
});
