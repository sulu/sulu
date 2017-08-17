/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Modal from '../Modal';

afterEach(() => document.body.innerHTML = '');

test('The component should render in body when open', () => {
    const body = document.body;
    const view = mount(<Modal isOpen={true}><p>Modal content</p></Modal>).render();
    expect(view).toMatchSnapshot();
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The component should not render in body when closed', () => {
    const body = document.body;
    const view = mount(<Modal isOpen={false}><p>Modal content</p></Modal>).render();
    expect(view).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The component should request to be closed on click on backdrop', () => {
    const requestCloseSpy = jest.fn();
    const view = shallow(<Modal isOpen={false} onRequestClose={requestCloseSpy}><p>Modal content</p></Modal>);
    const backdrop = view.find('Backdrop');
    expect(backdrop.length).toBe(1);

    expect(requestCloseSpy).toHaveBeenCalledTimes(0);
    backdrop.props().onClick();
    expect(requestCloseSpy).toHaveBeenCalledTimes(1);
});
