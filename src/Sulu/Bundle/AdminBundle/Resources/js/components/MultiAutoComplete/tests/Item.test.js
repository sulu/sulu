// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Item from '../Item';

test('Should render item with children', () => {
    expect(render(<Item onDelete={jest.fn()} value={{}}>Name</Item>)).toMatchSnapshot();
});

test('Should call onDelete callback when the times icon is clicked', () => {
    const deleteSpy = jest.fn();
    const value = {name: 'Test'};
    const item = shallow(<Item onDelete={deleteSpy} value={value}>Test</Item>);

    item.find('Icon').simulate('click');

    expect(deleteSpy).toBeCalledWith(value);
});
