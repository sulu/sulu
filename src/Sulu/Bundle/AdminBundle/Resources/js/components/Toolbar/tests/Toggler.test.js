// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import Toggler from '../Toggler';

test('Render disabled toggler', () => {
    expect(render(
        <Toggler disabled={true} label="Disabled Toggler" onClick={jest.fn()} value={false} />
    )).toMatchSnapshot();
});

test('Render toggler with skin', () => {
    expect(render(
        <Toggler label="Dark Toggler" onClick={jest.fn()} skin="dark" value={false} />
    )).toMatchSnapshot();
});

test('Render with active toggler', () => {
    expect(render(
        <Toggler label="Active Toggler" onClick={jest.fn()} value={true} />
    )).toMatchSnapshot();
});

test('Call onClick handler when item was clicked', () => {
    const clickSpy = jest.fn();
    const toggler = shallow(<Toggler label="Click Toggler" onClick={clickSpy} value={false} />);

    toggler.find('Button').simulate('click');

    expect(clickSpy).toBeCalledWith();
});
