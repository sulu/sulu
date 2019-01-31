// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import EditLine from '../EditLine';

test('Render an EditLine', () => {
    expect(render(<EditLine id="1" onChange={jest.fn()} onRemove={jest.fn()} value="Test" />)).toMatchSnapshot();
});

test('Call onChange callback if input changes', () => {
    const changeSpy = jest.fn();
    const editLine = shallow(<EditLine id={3} onChange={changeSpy} onRemove={jest.fn()} value="old" />);

    editLine.find('Input').simulate('change', 'new');

    expect(changeSpy).toBeCalledWith(3, 'new');
});

test('Call onRemove callback if line is removed', () => {
    const removeSpy = jest.fn();
    const editLine = shallow(<EditLine id={3} onChange={jest.fn()} onRemove={removeSpy} value="old" />);

    editLine.find('Button').simulate('click');

    expect(removeSpy).toBeCalledWith(3);
});
