// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Dropdown from '../Dropdown';

const dropdownPropsMock = {
    label: 'Click to open',
    options: [
        {
            label: 'An option',
            onClick: () => {},
        },
    ],
};

test('Render dropdown', () => {
    const {container} = render(<Dropdown {...dropdownPropsMock} />);
    expect(container).toMatchSnapshot();
});

test('Render loading dropdown', () => {
    const {container} = render(<Dropdown {...dropdownPropsMock} loading={true} />);
    expect(container).toMatchSnapshot();
});

test('Render disabled dropdown', () => {
    const {container} = render(
        <Dropdown
            {...dropdownPropsMock}
            disabled={true}
        />
    );
    expect(container).toMatchSnapshot();
});

test('Render dropdown with a prepended icon', () => {
    const {container} = render(
        <Dropdown
            {...dropdownPropsMock}
            icon="fa-floppy-o"
        />
    );
    expect(container).toMatchSnapshot();
});

test('Render dropdown without text', () => {
    const {container} = render(
        <Dropdown
            {...dropdownPropsMock}
            showText={false}
        />
    );
    expect(container).toMatchSnapshot();
});

test('Render dropdown with a different size', () => {
    const {container} = render(
        <Dropdown
            {...dropdownPropsMock}
            size="small"
        />
    );
    expect(container).toMatchSnapshot();
});

test('Open dropdown on click', async() => {
    render(<Dropdown {...dropdownPropsMock} />);

    const button = screen.queryByText('Click to open');

    expect(screen.queryByText('An option')).not.toBeInTheDocument();
    await userEvent.click(button);
    expect(screen.getByText('An option')).toBeInTheDocument();
});

test('Disabled dropdown will not open', async() => {
    render(
        <Dropdown
            {...dropdownPropsMock}
            disabled={true}
        />
    );

    const button = screen.queryByText('Click to open');

    expect(screen.queryByText('An option')).not.toBeInTheDocument();
    await userEvent.click(button);
    expect(screen.queryByText('An option')).not.toBeInTheDocument();
});

test('Click on option fires onClick', async() => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        options: [
            {
                label: 'An option',
                onClick: clickSpy,
            },
        ],
    };

    render(<Dropdown {...propsMock} />);

    await userEvent.click(screen.queryByText('Click to open'));
    await userEvent.click(screen.queryByText('An option'));

    expect(clickSpy).toBeCalled();
});

test('Click on disabled option will not fire onClick', async() => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        options: [
            {
                label: 'An option',
                onClick: clickSpy,
                disabled: true,
            },
            {
                label: 'Another option',
                onClick: jest.fn(),
                disabled: false,
            },
        ],
    };

    render(<Dropdown {...propsMock} />);

    await userEvent.click(screen.queryByText('Click to open'));
    await userEvent.click(screen.queryByText('An option'));

    expect(clickSpy).not.toBeCalled();
});

test('No active options should disable dropdown', async() => {
    const propsMock = {
        label: 'Click to open',
        options: [
            {
                label: 'An option',
                onClick: jest.fn(),
                disabled: true,
            },
            {
                label: 'Another option',
                onClick: jest.fn(),
                disabled: true,
            },
        ],
    };

    render(<Dropdown {...propsMock} />);
    const button = screen.queryByRole('button');

    expect(button).toBeDisabled();
    expect(screen.queryByText('An option')).not.toBeInTheDocument();

    // click on button shouldn't open the options
    await userEvent.click(button);
    expect(screen.queryByText('An option')).not.toBeInTheDocument();
});
