/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Button from '../Button';

test('Button should render with skin primary', () => {
    expect(render(<Button skin="primary" />)).toMatchSnapshot();
});

test('Button should render with skin secondary', () => {
    expect(render(<Button skin="secondary" />)).toMatchSnapshot();
});

test('Button should render with skin link', () => {
    expect(render(<Button skin="link" />)).toMatchSnapshot();
});

test('Button should call the callback on click', () => {
    const onClick = jest.fn();
    const button = shallow(<Button skin="primary" onClick={onClick} />);
    button.find('button').simulate('click');
    expect(onClick).toBeCalled();
});
