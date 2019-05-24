// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Action from '../Action';

test('Render default Action', () => {
    expect(render(<Action onClick={jest.fn()}>My Action</Action>)).toMatchSnapshot();
});

test('Render Action with icon', () => {
    expect(render(<Action icon="su-display-default" onClick={jest.fn()}>My Action</Action>)).toMatchSnapshot();
});

test('Render disabled Action', () => {
    expect(render(<Action disabled={true} onClick={jest.fn()}>My Action</Action>)).toMatchSnapshot();
});

test('Clicking the Action should call the right handler', () => {
    const clickHandler = jest.fn();

    const action = mount(<Action onClick={clickHandler}>My Action</Action>);

    action.simulate('click');
    expect(clickHandler).toBeCalledWith(undefined);
});

test('Clicking the Action should call the right handler with the passed value', () => {
    const clickHandler = jest.fn();

    const action = mount(<Action onClick={clickHandler} value="test">My Action</Action>);

    action.simulate('click');
    expect(clickHandler).toBeCalledWith('test');
});

test('Clicking the disabled Action should not call a handler', () => {
    const clickHandler = jest.fn();

    const action = mount(<Action disabled={true} onClick={clickHandler}>My Action</Action>);

    action.simulate('click');
    expect(clickHandler).not.toBeCalled();
});
