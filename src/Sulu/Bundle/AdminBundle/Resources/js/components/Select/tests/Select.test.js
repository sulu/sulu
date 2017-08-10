/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Select from '../Select';
import Divider from '../Divider';
import Option from '../Option';

afterEach(() => document.body.innerHTML = '');

test('The component should render with the list closed', () => {
    const body = document.body;
    const select = mount(
        <Select>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should render with an icon', () => {
    const body = document.body;
    const select = mount(
        <Select icon="plus">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should render with the correct value', () => {
    const body = document.body;
    const select = mount(
        <Select value="option-2">
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should open the list when the label is clicked', () => {
    window.requestAnimationFrame = (cb) => cb();
    const body = document.body;
    const select = mount(
        <Select>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.instance().handleLabelClick();
    expect(select.render()).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should trigger the change callback and close the list when an option is clicked', () => {
    window.requestAnimationFrame = (cb) => cb();
    const body = document.body;
    const onChangeSpy = jest.fn();
    const select = mount(
        <Select onChange={onChangeSpy}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.instance().handleLabelClick();
    body.getElementsByTagName('button')[2].click();
    expect(onChangeSpy).toHaveBeenCalledWith('option-3');
    expect(body.innerHTML).toBe('');
});
