/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Item from '../Item';

test('Render item', () => {
    expect(render(<Item />)).toMatchSnapshot();
});

test('Render disabled item', () => {
    expect(render(<Item enabled={false} />)).toMatchSnapshot();
});

test('Click on item fires onClick callback', () => {
    const clickSpy = jest.fn();
    const item = shallow(<Item onClick={clickSpy} />);

    item.simulate('click');

    expect(clickSpy).toBeCalled();
});

test('Click on item does not fire onClick callback if item is disabled', () => {
    const clickSpy = jest.fn();
    const item = shallow(<Item onClick={clickSpy} enabled={false} />);

    item.simulate('click');

    expect(clickSpy).toHaveBeenCalledTimes(0);
});
