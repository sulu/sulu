/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import React from 'react';
import Label from '../Label';

test('The component should render', () => {
    const label = render(<Label>My label</Label>);
    expect(label).toMatchSnapshot();
});

test('The component should render with an icon', () => {
    const label = render(<Label icon="plus">My label</Label>);
    expect(label).toMatchSnapshot();
});

test('A click on the component should fire the callback', () => {
    const clickSpy = jest.fn();
    const label = shallow(<Label onClick={clickSpy}>My label</Label>);
    label.simulate('click');
    expect(clickSpy).toBeCalled();
});
