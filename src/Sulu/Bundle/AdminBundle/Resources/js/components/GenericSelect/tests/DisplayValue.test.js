/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import React from 'react';
import DisplayValue from '../DisplayValue';

test('The component should render', () => {
    const label = render(<DisplayValue>My label</DisplayValue>);
    expect(label).toMatchSnapshot();
});

test('The component should render with an icon', () => {
    const label = render(<DisplayValue icon="plus">My label</DisplayValue>);
    expect(label).toMatchSnapshot();
});

test('A click on the component should fire the callback', () => {
    const clickSpy = jest.fn();
    const label = shallow(<DisplayValue onClick={clickSpy}>My label</DisplayValue>);
    label.simulate('click');
    expect(clickSpy).toBeCalled();
});
