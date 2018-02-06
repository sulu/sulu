// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Backdrop from '../Backdrop';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('The component should render in body when open', () => {
    const body = document.body;
    const view = mount(<Backdrop open={true} />).render();
    expect(view.html()).toBe('');
    expect(pretty(body ? body.innerHTML : '')).toMatchSnapshot();
});

test('The component should render normally when local property is set', () => {
    const body = document.body;
    const view = mount(<Backdrop local={true} open={true} />).render();
    expect(view).toMatchSnapshot();
    expect(body ? body.innerHTML : '').toBe('');
});

test('The component should not render local when closed', () => {
    const body = document.body;
    const view = mount(<Backdrop local={true} open={false} />).render();
    expect(view).toMatchSnapshot();
    expect(body ? body.innerHTML : '').toBe('');
});

test('The component should not render in the body when closed', () => {
    const body = document.body;
    const view = mount(<Backdrop open={false} />).render();
    expect(view.html()).toBe(null);
    expect(body ? body.innerHTML : '').toBe('');
});

test('The component should call a function when clicked', () => {
    const onClickSpy = jest.fn();
    const view = shallow(<Backdrop open={true} onClick={onClickSpy} />);

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    view.find('.backdrop').simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
