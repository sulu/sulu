/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import ClickModal from '../ClickModal';

afterEach(() => document.body.innerHTML = '');

test('The modal should initially not be rendered', () => {
    const body = document.body;
    const view = mount(
        <ClickModal clickElement={<button>Open modal</button>}>
            <p>Modal content</p>
        </ClickModal>
    ).render();
    expect(view).toMatchSnapshot();
    expect(body.innerHTML).toBe('');
});

test('The modal should be rendered when the button got clicked', () => {
    const body = document.body;
    const view = mount(
        <ClickModal clickElement={<button>Open modal</button>}>
            <p>Modal content</p>
        </ClickModal>
    );
    view.render();

    expect(body.innerHTML).toBe('');
    view.find('button').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('The modal should close again when backdrop got clicked', () => {
    const body = document.body;
    const view = mount(
        <ClickModal clickElement={<button>Open modal</button>}>
            <p>Modal content</p>
        </ClickModal>
    );
    view.render();

    expect(body.innerHTML).toBe('');
    view.find('button').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
    body.getElementsByClassName('backdrop')[0].click();
    expect(body.innerHTML).toBe('');
});
