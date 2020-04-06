// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import Backdrop from '../Backdrop';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('The component should render in body when open', () => {
    const view = mount(<Backdrop open={true} />);
    expect(view.find('Backdrop > Portal').render()).toMatchSnapshot();
});

test('The component should render normally when local property is set', () => {
    const view = mount(<Backdrop local={true} open={true} />);
    expect(view.find('Portal')).toHaveLength(0);
    expect(view.render()).toMatchSnapshot();
});

test('The component should not render local when closed', () => {
    const view = mount(<Backdrop local={true} open={false} />);
    expect(view.children()).toHaveLength(0);
});

test('The component should not render in the body when closed', () => {
    const view = mount(<Backdrop open={false} />).render();
    expect(view.children()).toHaveLength(0);
});

test('The component should call a function when clicked', () => {
    const onClickSpy = jest.fn();
    const view = shallow(<Backdrop onClick={onClickSpy} open={true} />);

    expect(onClickSpy).toHaveBeenCalledTimes(0);
    view.find('.backdrop').simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
