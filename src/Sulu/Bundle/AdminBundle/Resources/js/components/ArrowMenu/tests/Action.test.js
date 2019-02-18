// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import Action from '../Action';

test('Render default Action', () => {
    const clickHandler = jest.fn();
    expect(render(<Action onClick={clickHandler}>My Action</Action>)).toMatchSnapshot();
});

test('Render disabled Action', () => {
    const clickHandler = jest.fn();
    expect(render(<Action disabled={true} onClick={clickHandler}>My Action</Action>)).toMatchSnapshot();
});

test('Clicking the Action should call the right handler', () => {
    const clickHandler = jest.fn();

    const action = mount(
        <Action onClick={clickHandler}>My Action</Action>
    );

    action.simulate('click');
    expect(clickHandler).toBeCalled();
});
