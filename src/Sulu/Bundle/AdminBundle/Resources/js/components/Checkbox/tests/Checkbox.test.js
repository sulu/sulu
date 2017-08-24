/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow, render} from 'enzyme';
import React from 'react';
import Checkbox from '../Checkbox';

test('The component should render in unchecked state', () => {
    const checkbox = render(<Checkbox checked={false} />);
    expect(checkbox).toMatchSnapshot();
});

test('The component should render in checked state', () => {
    const checkbox = render(<Checkbox checked={true} />);
    expect(checkbox).toMatchSnapshot();
});

test('The component should render in with class', () => {
    const checkbox = render(<Checkbox className="my-class" checked={false} />);
    expect(checkbox).toMatchSnapshot();
});

test('The component should render with light skin', () => {
    const checkbox = render(<Checkbox checked={false} skin="light" />);
    expect(checkbox).toMatchSnapshot();
});

test('A click on the checkbox should trigger the change callback', () => {
    const onChangeSpy = jest.fn();
    const checkbox = shallow(<Checkbox checked={false} onChange={onChangeSpy} />);
    checkbox.find('input').simulate('change', {currentTarget: {checked: true}});
    expect(onChangeSpy).toHaveBeenCalledWith(true, undefined);
    checkbox.find('input').simulate('change', {currentTarget: {checked: false}});
    expect(onChangeSpy).toHaveBeenCalledWith(false, undefined);
});

test('A click on the checkbox should trigger the change callback with the value', () => {
    const onChangeSpy = jest.fn();
    const checkbox = shallow(<Checkbox checked={false} value="my-value" onChange={onChangeSpy} />);
    checkbox.find('input').simulate('change', {currentTarget: {checked: true}});
    expect(onChangeSpy).toHaveBeenCalledWith(true, 'my-value');
    checkbox.find('input').simulate('change', {currentTarget: {checked: false}});
    expect(onChangeSpy).toHaveBeenCalledWith(false, 'my-value');
});
