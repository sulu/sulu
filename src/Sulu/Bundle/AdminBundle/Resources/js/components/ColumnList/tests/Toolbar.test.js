// @flow
import React from 'react';
import {mount} from 'enzyme';
import Toolbar from '../Toolbar';
import ToolbarDropdown from '../ToolbarDropdown';

test('The Toolbar component should render with active', () => {
    const body = document.body;
    if (!body) {
        throw new Error('Body tag should exist!');
    }

    const toolbarItems = [
        {
            icon: 'plus',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'search',
            type: 'button',
            onClick: () => {},
        },
        {
            icon: 'gear',
            type: 'dropdown',
            options: [
                {
                    label: 'Option1',
                    onClick: () => {},
                },
                {
                    label: 'Option2',
                    onClick: () => {},
                },
            ],
        },
    ];

    const toolbar = mount(
        <Toolbar active={true} index={0} toolbarItems={toolbarItems} />
    );

    expect(toolbar.render()).toMatchSnapshot();
    expect(toolbar.find(ToolbarDropdown).length).toBe(1);

    // check for opened dropdown in body
    expect(body.innerHTML).toBe('');
    toolbar.find(ToolbarDropdown).simulate('click');
    expect(body.innerHTML).not.toBe('');
    expect(body.innerHTML).toMatchSnapshot();

    const toolbarActive = mount(
        <Toolbar active={false} index={0} toolbarItems={toolbarItems} />
    );

    expect(toolbarActive.render()).toMatchSnapshot();
});
