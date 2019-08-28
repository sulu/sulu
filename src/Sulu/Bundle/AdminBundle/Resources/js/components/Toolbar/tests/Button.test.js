// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import Button from '../Button';

test('Render button', () => {
    expect(render(<Button onClick={jest.fn()}>Click</Button>)).toMatchSnapshot();
});

test('Render success button', () => {
    expect(render(<Button onClick={jest.fn()} success={true}>Click</Button>)).toMatchSnapshot();
});

test('Render primary button', () => {
    expect(render(<Button onClick={jest.fn()} primary={true}>Click</Button>)).toMatchSnapshot();
});

test('Render primary success button', () => {
    expect(render(<Button onClick={jest.fn()} primary={true} success={true}>Click</Button>)).toMatchSnapshot();
});

test('Render loading button', () => {
    expect(render(<Button loading={true} onClick={jest.fn()}>Click</Button>)).toMatchSnapshot();
});

test('Render button with value', () => {
    expect(render(<Button label="Click" onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Render button without text', () => {
    expect(render(<Button onClick={jest.fn()} showText={false} />)).toMatchSnapshot();
});

test('Render disabled button', () => {
    expect(render(<Button disabled={true} onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Click on button fires onClick callback', () => {
    const clickSpy = jest.fn();
    const button = shallow(<Button onClick={clickSpy} />);

    button.simulate('click');

    expect(clickSpy).toBeCalled();
});

test('Render button with dropdown indicator', () => {
    expect(render(<Button hasOptions={true} onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Render button with a different size', () => {
    expect(render(<Button onClick={jest.fn()} size="small" />)).toMatchSnapshot();
});

test('Render button with a prepended icon', () => {
    expect(render(<Button icon="fa-trash-o" onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Render an active button', () => {
    expect(render(<Button active={true} onClick={jest.fn()} />)).toMatchSnapshot();
});

test('Click on button does not fire onClick callback if button is disabled', () => {
    const clickSpy = jest.fn();
    const button = mount(<Button disabled={true} onClick={clickSpy} />);

    button.simulate('click');

    expect(clickSpy).toHaveBeenCalledTimes(0);
});
