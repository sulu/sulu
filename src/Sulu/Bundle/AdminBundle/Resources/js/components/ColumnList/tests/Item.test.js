// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Item from '../Item';

test('Should render item as not selected by default', () => {
    expect(render(<Item id={1} order={1}>Test</Item>)).toMatchSnapshot();
});

test('Should render item as selected', () => {
    expect(render(<Item id={1} selected={true} order={2}>Test</Item>)).toMatchSnapshot();
});

test('Should render item as disabled', () => {
    expect(render(<Item id={1} disabled={true} order={3}>Test</Item>)).toMatchSnapshot();
});

test('Should render item with indicators', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];

    expect(render(<Item id={2} indicators={indicators} order={4}>Test with indicators</Item>)).toMatchSnapshot();
});

test('Should render item with order input', () => {
    const indicators = [
        <span key={1}>ghost</span>,
        <span key={2}>shadow</span>,
    ];

    expect(render(<Item id={2} indicators={indicators} order={4} showOrderField={true}>Test with indicators</Item>))
        .toMatchSnapshot();
});

test('Should call onOrderChange callback when order has changed', () => {
    const orderChangeSpy = jest.fn();
    const item = shallow(
        <Item id={2} onOrderChange={orderChangeSpy} order={4} showOrderField={true}>Test with indicators</Item>
    );

    item.find('Input').simulate('change', 5);
    item.find('Input').simulate('blur');
    expect(orderChangeSpy).toBeCalledWith(2, 5);
});

test('Should change order when item receives new props', () => {
    const item = shallow(
        <Item id={2} order={4} showOrderField={true}>Test with indicators</Item>
    );

    expect(item.find('Input').prop('value')).toEqual(4);

    item.find('Input').simulate('change', 5);
    expect(item.find('Input').prop('value')).toEqual(5);

    item.setProps({order: 1});
    expect(item.find('Input').prop('value')).toEqual(1);
});
