/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {shallow} from 'enzyme';
import Item from '../Item';

test('Click on item fires onClick callback', () => {
    const clickSpy = jest.fn();
    const button = shallow(<Item onClick={clickSpy} />);

    button.simulate('click');

    expect(clickSpy).toBeCalled();
});
