// @flow
import {mount} from 'enzyme';
import React from 'react';
import Menu from '../Menu.js';

test('The component should render a menu list', () => {
    const view = mount(
        <Menu>
            <li>Item 1</li>
            <li>Item 2</li>
            <li>Item 3</li>
        </Menu>
    ).render();
    expect(view).toMatchSnapshot();
});
