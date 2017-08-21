/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Backdrop from '../Backdrop';

afterEach(() => document.body.innerHTML = '');

test('The component should render in body when open', () => {
    const body = document.body;
    const view = mount(<Backdrop isOpen={true} />).render();
    expect(view.html()).toBe(null);
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should not render in body when property is set', () => {
    const view = mount(<Backdrop inPortal={false} />).render();
    expect(view).toMatchSnapshot();
});

test('The component should not render in the body when closed', () => {
    const body = document.body;
    const view = mount(<Backdrop isOpen={false} />).render();
    expect(view.html()).toBe(null);
    expect(body.innerHTML).toBe('');
});

test('The component should call a function when clicked', () => {
    const onClickSpy = jest.fn();
    const view = shallow(<Backdrop isOpen={false} onClick={onClickSpy} />);

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    view.find('.backdrop').simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
