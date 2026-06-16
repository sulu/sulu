// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Toolbar from '../Toolbar';

test('Should render with active', async() => {
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
    const user = userEvent.setup();
    const {baseElement} = render(<Toolbar toolbarItems={toolbarItems} />);

    await user.click(screen.getByRole('button', {name: 'fa-plus'}));
    expect(toolbarItems[0].onClick).toHaveBeenCalledWith();

    // check for opened dropdown in body
    await user.click(screen.getByRole('button', {name: 'fa-gear su-angle-down'}));
    expect(screen.getAllByText('Option1')).toHaveLength(2);
    expect(baseElement).toMatchSnapshot();
});

test('Should close dropdown when item is clicked', async() => {
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
    const user = userEvent.setup();

    render(<Toolbar toolbarItems={toolbarItems} />);

    expect(screen.queryByText('Option1')).not.toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'fa-gear su-angle-down'}));
    expect(screen.getByText('Option1')).toBeInTheDocument();
    expect(screen.getByText('Option2')).toBeInTheDocument();

    await user.click(screen.getByText('Option1'));
    expect(screen.queryByText('Option1')).not.toBeInTheDocument();
});
