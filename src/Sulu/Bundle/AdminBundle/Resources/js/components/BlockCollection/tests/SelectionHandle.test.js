// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import SelectionHandle from '../SelectionHandle';

test('Render selection handle unchecked', () => {
    expect(render(
        <SelectionHandle checked={false} onChange={jest.fn()} />
    )).toMatchSnapshot();
});

test('Render selection handle checked', () => {
    expect(render(
        <SelectionHandle checked={true} onChange={jest.fn()} />
    )).toMatchSnapshot();
});

test('Change checkbox should trigger onChange', () => {
    const changeSpy = jest.fn();

    const component = shallow(
        <SelectionHandle checked={true} onChange={changeSpy} />
    );

    expect(component.find('Checkbox').length).toBe(1);

    component.find('Checkbox').simulate('change');

    expect(changeSpy).toBeCalled();
});

test('Click on container should trigger onChange', () => {
    const changeSpy = jest.fn();

    const component = mount(
        <SelectionHandle checked={true} onChange={changeSpy} />
    );

    component.simulate('click', {stopPropagation: jest.fn()});

    expect(changeSpy).toBeCalled();
});
