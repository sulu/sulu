/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import Button from '../Button';

test('Render button', () => {
    expect(render(<Button>Click</Button>)).toMatchSnapshot();
});

test('Render loading button', () => {
    expect(render(<Button loading={true}>Click</Button>)).toMatchSnapshot();
});

test('Render button with value', () => {
    expect(render(<Button value="Click" />)).toMatchSnapshot();
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

test('Render button with dropdown indicator', () => {
    expect(render(<Button hasOptions={true} />)).toMatchSnapshot();
});

test('Render button with a different size', () => {
    expect(render(<Button size="small" />)).toMatchSnapshot();
});

test('Render button with a prepended icon', () => {
    expect(render(<Button icon="fa-trash-o" />)).toMatchSnapshot();
});

test('Render an active button', () => {
    expect(render(<Button active={true} />)).toMatchSnapshot();
});

test('Click on button does not fire onClick callback if button is disabled', () => {
    const clickSpy = jest.fn();
    const button = mount(<Button disabled={true} onClick={clickSpy} />);

    button.simulate('click');

    expect(clickSpy).toHaveBeenCalledTimes(0);
});
