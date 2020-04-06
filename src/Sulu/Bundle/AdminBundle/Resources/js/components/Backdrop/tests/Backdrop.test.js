// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import Backdrop from '../Backdrop';

test('The component should render', () => {
    expect(render(<Backdrop />)).toMatchSnapshot();
});

test('The component should call a function when clicked', () => {
    const onClickSpy = jest.fn();
    const view = shallow(<Backdrop onClick={onClickSpy} />);

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    view.find('.backdrop').simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
