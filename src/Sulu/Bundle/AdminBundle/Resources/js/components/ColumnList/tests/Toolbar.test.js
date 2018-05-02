// @flow
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import Toolbar from '../Toolbar';
import ToolbarDropdown from '../ToolbarDropdown';

test('The Toolbar component should render with active', () => {
    const body = document.body;
    if (!body) {
        throw new Error('Body tag should exist!');
    }

    const toolbarItems = [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: jest.fn(),
        },
        {
            icon: 'fa-gear',
            type: 'dropdown',
            options: [
                {
                    label: 'Option1',
                    onClick: jest.fn(),
                },
            ],
        },
    ];

    const toolbar = mount(<Toolbar columnIndex={0} toolbarItems={toolbarItems} />);

    expect(toolbar.render()).toMatchSnapshot();
    expect(toolbar.find(ToolbarDropdown).length).toBe(1);

    toolbar.find('.fa-plus').simulate('click');
    expect(toolbarItems[0].onClick).toBeCalledWith(0);

    // check for opened dropdown in body
    expect(body.innerHTML).toBe('');
    toolbar.find(ToolbarDropdown).simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});
