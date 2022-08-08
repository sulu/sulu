// @flow
import {render} from '@testing-library/react';
import React from 'react';
import Menu from '../Menu.js';

test('The component should render a menu list', () => {
    const {container} = render(
        <Menu>
            <li>Item 1</li>
            <li>Item 2</li>
            <li>Item 3</li>
        </Menu>
    );
    expect(container).toMatchSnapshot();
});
