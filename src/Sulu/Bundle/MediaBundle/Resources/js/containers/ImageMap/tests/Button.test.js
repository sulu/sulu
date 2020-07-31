// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Button from '../Button';

test('Should render with icon', () => {
    expect(render(<Button icon="su-plus-circle" onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Should render with icon and disabled', () => {
    expect(render(<Button disabled={true} icon="su-plus-circle" onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Should call the callback on click', () => {
    const preventDefaultSpy = jest.fn();
    const onClick = jest.fn();
    const button = shallow(<Button icon="su-plus-circle" onClick={onClick} />);
    button.find('button').simulate('click', {preventDefault: preventDefaultSpy});
    expect(preventDefaultSpy).toBeCalled();
    expect(onClick).toBeCalled();
});
