/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Button from '../Button';

test('Button should render', () => {
    expect(render(<Button type="confirm" />)).toMatchSnapshot();
});

test('Button should render with class names', () => {
    expect(render(<Button className="test" name="edit" />)).toMatchSnapshot();
});

test('Button should render with onClick handler, role and tabindex', () => {
    const onClickSpy = jest.fn();
    expect(render(<Button className="test" name="save" onClick={onClickSpy} />)).toMatchSnapshot();
});

test('Button should call the callback on click', () => {
    const onClick = jest.fn();
    const icon = shallow(<Button className="test" name="edit" onClick={onClick} />);
    icon.simulate('click');
    expect(onClick).toBeCalled();
});
