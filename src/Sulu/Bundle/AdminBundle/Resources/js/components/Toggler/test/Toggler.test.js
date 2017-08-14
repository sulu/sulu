/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow, render} from 'enzyme';
import React from 'react';
import Toggler from '../Toggler';

test('The component should render in unchecked state', () => {
    const toggler = render(<Toggler checked={false} />);
    expect(toggler).toMatchSnapshot();
});

test('The component should render in checked state', () => {
    const toggler = render(<Toggler checked={true} />);
    expect(toggler).toMatchSnapshot();
});

test('A click on the toggler should trigger the change callback', () => {
    const onChangeSpy = jest.fn();
    const toggler = shallow(<Toggler checked={false} onChange={onChangeSpy} />);
    toggler.find('input').simulate('change', {currentTarget: {checked: true}});
    expect(onChangeSpy).toHaveBeenCalledWith(true);
    toggler.find('input').simulate('change', {currentTarget: {checked: false}});
    expect(onChangeSpy).toHaveBeenCalledWith(false);
});
