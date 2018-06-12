/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow, render} from 'enzyme';
import React from 'react';
import Switch from '../Switch';

test('The component should render in unchecked state', () => {
    const component = render(<Switch checked={false} />);
    expect(component).toMatchSnapshot();
});

test('The component should render in checked state', () => {
    const component = render(<Switch checked={true} />);
    expect(component).toMatchSnapshot();
});

test('The component should render with class', () => {
    const component = render(<Switch className="my-class" checked={false} />);
    expect(component).toMatchSnapshot();
});

test('The component should render in inactive state', () => {
    const component = render(<Switch active={false} />);
    expect(component).toMatchSnapshot();
});

test('The component should render with name', () => {
    const component = render(<Switch name="my-name" checked={false} />);
    expect(component).toMatchSnapshot();
});

test('The component should render without a label container', () => {
    const component = render(<Switch name="my-name" checked={false} />);
    expect(component).toMatchSnapshot();
});

test('The component should render with radio type', () => {
    const component = render(<Switch type="radio" className="my-class" checked={false} />);
    expect(component).toMatchSnapshot();
});

test('A click on the checkbox should trigger the change callback', () => {
    const onChangeSpy = jest.fn();
    const component = shallow(<Switch checked={false} onChange={onChangeSpy} />);
    component.find('input').simulate('change', {currentTarget: {checked: true}});
    expect(onChangeSpy).toHaveBeenCalledWith(true, undefined);
    component.find('input').simulate('change', {currentTarget: {checked: false}});
    expect(onChangeSpy).toHaveBeenCalledWith(false, undefined);
});

test('A click on the checkbox should trigger the change callback with the value', () => {
    const onChangeSpy = jest.fn();
    const component = shallow(<Switch checked={false} value="my-value" onChange={onChangeSpy} />);
    component.find('input').simulate('change', {currentTarget: {checked: true}});
    expect(onChangeSpy).toHaveBeenCalledWith(true, 'my-value');
    component.find('input').simulate('change', {currentTarget: {checked: false}});
    expect(onChangeSpy).toHaveBeenCalledWith(false, 'my-value');
});

test('A click on the checkbox should stop the further propagation of the DOM event', () => {
    const stopPropagationSpy = jest.fn();
    const component = shallow(<Switch />);

    component.find('label').simulate('click', {stopPropagation: stopPropagationSpy});
    expect(stopPropagationSpy).toBeCalledWith();
});
