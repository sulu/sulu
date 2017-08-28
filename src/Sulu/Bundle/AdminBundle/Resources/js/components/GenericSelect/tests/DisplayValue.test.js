/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import React from 'react';
import DisplayValue from '../DisplayValue';

test('The component should render', () => {
    const displayValue = render(<DisplayValue>My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('The component should render with an icon', () => {
    const displayValue = render(<DisplayValue icon="plus">My value</DisplayValue>);
    expect(displayValue).toMatchSnapshot();
});

test('A click on the component should fire the callback', () => {
    const clickSpy = jest.fn();
    const displayValue = shallow(<DisplayValue onClick={clickSpy}>My value</DisplayValue>);
    displayValue.simulate('click');
    expect(clickSpy).toBeCalled();
});
