// @flow
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import Toolbar from '../Toolbar';
import ToolbarDropdown from '../ToolbarDropdown';

test('Should render with active', () => {
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
                {
                    disabled: true,
                    label: 'Option1',
                    onClick: jest.fn(),
                },
            ],
        },
    ];

    const toolbar = mount(<Toolbar toolbarItems={toolbarItems} />);

    expect(toolbar.render()).toMatchSnapshot();
    expect(toolbar.find(ToolbarDropdown).length).toBe(1);

    toolbar.find('.fa-plus').simulate('click');
    expect(toolbarItems[0].onClick).toBeCalledWith();

    // check for opened dropdown in body
    expect(body.innerHTML).toBe('');
    toolbar.find(ToolbarDropdown).find('a').simulate('click');
    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('Should close dropdown when item is clicked', () => {
    const toolbarItems = [
        {
            icon: 'fa-gear',
            type: 'dropdown',
            options: [
                {
                    label: 'Option1',
                    onClick: jest.fn(),
                },
                {
                    label: 'Option2',
                    onClick: jest.fn(),
                },
            ],
        },
    ];

    const toolbar = mount(<Toolbar toolbarItems={toolbarItems} />);

    expect(toolbar.find('ToolbarDropdown').find('Action')).toHaveLength(0);
    toolbar.find(ToolbarDropdown).find('a').simulate('click');
    expect(toolbar.find('ToolbarDropdown').find('Action')).toHaveLength(2);
    toolbar.find('ToolbarDropdown Action[children="Option1"]').simulate('click');
    expect(toolbar.find('ToolbarDropdown').find('Action')).toHaveLength(0);
});
