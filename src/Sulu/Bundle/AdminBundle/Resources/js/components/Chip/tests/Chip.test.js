// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Chip from '../Chip';

test('Should render item with children', () => {
    expect(render(<Chip value={{}}>Name</Chip>)).toMatchSnapshot();
});

test('Should render item with delete icon', () => {
    expect(render(<Chip onDelete={jest.fn()} value={{}}>Name</Chip>)).toMatchSnapshot();
});

test('Should render item without icon in disabled state', () => {
    expect(render(<Chip disabled={true} onDelete={jest.fn()} value={{}}>Name</Chip>)).toMatchSnapshot();
});

test('Should call onClick callback when the button is clicked', () => {
    const clickSpy = jest.fn();
    const value = {name: 'Test'};
    const item = shallow(<Chip onClick={clickSpy} onDelete={jest.fn()} value={value}>Test</Chip>);

    item.find('button').simulate('click');

    expect(clickSpy).toBeCalledWith(value);
});

test('Should call onDelete callback when the times icon is clicked', () => {
    const deleteSpy = jest.fn();
    const value = {name: 'Test'};
    const item = shallow(<Chip onDelete={deleteSpy} value={value}>Test</Chip>);

    item.find('Icon').simulate('click');

    expect(deleteSpy).toBeCalledWith(value);
});
