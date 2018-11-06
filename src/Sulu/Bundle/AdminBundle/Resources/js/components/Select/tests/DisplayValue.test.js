// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import CroppedText from '../../CroppedText';
import DisplayValue from '../DisplayValue';

test('The component should render', () => {
    const displayValue = render(<DisplayValue onClick={jest.fn()}>My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('The component should render with the flat skin', () => {
    const displayValue = render(<DisplayValue onClick={jest.fn()} skin="flat">My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('The component should render with the dark skin', () => {
    const displayValue = render(<DisplayValue onClick={jest.fn()} skin="dark">My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('The component should render with an icon', () => {
    const displayValue = render(<DisplayValue icon="su-plus" onClick={jest.fn()}>My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('The component should render when disabled', () => {
    const displayValue = render(<DisplayValue disabled={true} onClick={jest.fn()}>My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('A click on the component should fire the callback and prevent the default', () => {
    const clickSpy = jest.fn();
    const preventDefaultSpy = jest.fn();

    const displayValue = shallow(<DisplayValue onClick={clickSpy}>My value</DisplayValue>);

    displayValue.simulate('click', {preventDefault: preventDefaultSpy});
    expect(clickSpy).toBeCalled();
    expect(preventDefaultSpy).toBeCalled();
});

test('The component should use the CroppedText component to cut long texts', () => {
    const displayValue = shallow(
        <DisplayValue onClick={jest.fn()}>This value should be wrapped in a CroppedText component</DisplayValue>
    );
    expect(displayValue.find(CroppedText)).toHaveLength(1);
});
