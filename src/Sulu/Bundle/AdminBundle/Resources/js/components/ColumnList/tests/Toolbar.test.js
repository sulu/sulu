// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Toolbar from '../Toolbar';
import ToolbarDropdown from '../ToolbarDropdown';

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should render with active', async() => {
    const user = userEvent.setup();
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
                    label: 'Option2',
                    onClick: jest.fn(),
                },
            ],
        },
    ];
    const {asFragment} = render(<Toolbar toolbarItems={toolbarItems} />);
    const toolbarButtons = screen.getAllByRole('button');

    expect(toolbarButtons).toHaveLength(2);
    expect(asFragment()).toMatchSnapshot();

    await user.click(toolbarButtons[0]);
    expect(toolbarItems[0].onClick).toBeCalledWith();

    expect(screen.queryByRole('button', {name: 'Option1'})).not.toBeInTheDocument();
    await user.click(toolbarButtons[1]);

    expect(screen.getByRole('button', {name: 'Option1'})).toBeEnabled();
    expect(screen.getByRole('button', {name: 'Option2'})).toBeDisabled();
});

test('Should close dropdown when item is clicked', async() => {
    const user = userEvent.setup();
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

    render(<ToolbarDropdown {...toolbarItems[0]} />);

    expect(screen.queryByRole('button', {name: 'Option1'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button'));
    expect(screen.getByRole('button', {name: 'Option1'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'Option1'}));
    expect(toolbarItems[0].options[0].onClick).toBeCalledTimes(1);
    expect(screen.queryByRole('button', {name: 'Option1'})).not.toBeInTheDocument();
});
