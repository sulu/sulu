/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import Button from '../Button';
import React from 'react';

test('Render button', () => {
    expect(render(<Button />)).toMatchSnapshot();
});

test('Render disabled button', () => {
    expect(render(<Button disabled={true} />)).toMatchSnapshot();
});

test('Click on button fires onClick callback', () => {
    const clickSpy = jest.fn();
    const button = shallow(<Button onClick={clickSpy} />);

    button.simulate('click');

    expect(clickSpy).toBeCalled();
});

test('Click on button does not fire onClick callback if button is disabled', () => {
    const clickSpy = jest.fn();
    const button = shallow(<Button onClick={clickSpy} disabled={true} />);

    button.simulate('click');

    expect(clickSpy).toHaveBeenCalledTimes(0);
});
