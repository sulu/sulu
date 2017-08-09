/* eslint-disable flowtype/require-valid-file-annotation */
import {render, shallow} from 'enzyme';
import Button from '../Button';
import React from 'react';

test('Render item', () => {
    const handleClick = () => {};

    expect(render(
        <Button
            onClick={handleClick} />
    )).toMatchSnapshot();
});

// test('Render disabled item', () => {
//     expect(render(<Item enabled={false} />)).toMatchSnapshot();
// });

// test('Click on item fires onClick callback', () => {
//     const clickSpy = jest.fn();
//     const item = shallow(<Item onClick={clickSpy} />);

//     item.simulate('click');

//     expect(clickSpy).toBeCalled();
// });

// test('Click on item does not fire onClick callback if item is disabled', () => {
//     const clickSpy = jest.fn();
//     const item = shallow(<Item onClick={clickSpy} enabled={false} />);

//     item.simulate('click');

//     expect(clickSpy).toHaveBeenCalledTimes(0);
// });
