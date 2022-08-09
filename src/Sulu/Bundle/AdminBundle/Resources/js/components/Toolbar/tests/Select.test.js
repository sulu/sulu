// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Select from '../Select';

const selectPropsMock = {
    label: 'Choose an option',
    onChange: () => {},
    options: [
        {
            value: 1,
            label: 'An option',
        },
    ],
    value: undefined,
};

test('Render select', () => {
    const {container} = render(<Select {...selectPropsMock} />);
    expect(container).toMatchSnapshot();
});

test('Render loading select', () => {
    const {container} = render(<Select {...selectPropsMock} loading={true} />);
    expect(container).toMatchSnapshot();
});

test('Render disabled select', () => {
    const {container} = render(
        <Select
            {...selectPropsMock}
            disabled={true}
        />
    );
    expect(container).toMatchSnapshot();
});

test('Render select with a prepended icon', () => {
    const {container} = render(
        <Select
            {...selectPropsMock}
            icon="fa-floppy-o"
        />
    );
    expect(container).toMatchSnapshot();
});

test('Render select without text', () => {
    const {container} = render(
        <Select
            {...selectPropsMock}
            showText={false}
        />
    );
    expect(container).toMatchSnapshot();
});

test('Render select with a different size', () => {
    const {container} = render(
        <Select
            {...selectPropsMock}
            size="small"
        />
    );
    expect(container).toMatchSnapshot();
});

test('Open select on click', async() => {
    render(<Select {...selectPropsMock} />);

    const button = screen.queryByText('Choose an option');

    expect(screen.queryByText('An option')).not.toBeInTheDocument();
    await userEvent.click(button);
    expect(screen.getByText('An option')).toBeInTheDocument();
});

test('Disabled select will not open', async() => {
    render(
        <Select
            {...selectPropsMock}
            disabled={true}
        />
    );

    const button = screen.queryByText('Choose an option');

    expect(screen.queryByText('An option')).not.toBeInTheDocument();
    await userEvent.click(button);
    expect(screen.queryByText('An option')).not.toBeInTheDocument();
});

test('Click on disabled option will not fire onChange', async() => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        onChange: clickSpy,
        options: [
            {
                value: 1,
                label: 'An option',
                disabled: true,
            },
        ],
        value: undefined,
    };

    render(<Select {...propsMock} />);

    await userEvent.click(screen.queryByText('Click to open'));
    await userEvent.click(screen.queryByText('An option'));

    expect(clickSpy).toHaveBeenCalledTimes(0);
});

test('Click on option fires onChange with the selected value as the first argument', async() => {
    const clickSpy = jest.fn();
    const propsMock = {
        label: 'Click to open',
        onChange: clickSpy,
        options: [
            {
                value: 1,
                label: 'An option',
            },
            {
                value: 2,
                label: 'Another option',
            },
        ],
        value: undefined,
    };

    render(<Select {...propsMock} />);

    await userEvent.click(screen.queryByText('Click to open'));
    await userEvent.click(screen.queryByText('An option'));

    expect(clickSpy.mock.calls[0][0]).toBe(1);
});

test('The label of the option is written in the toggle-button if you set the options value', () => {
    const clickSpy = jest.fn();
    const propsMock = {
        value: 2,
        label: 'Click to open',
        onChange: clickSpy,
        options: [
            {
                value: 1,
                label: 'An option',
            },
            {
                value: 2,
                label: 'Another option',
            },
        ],
    };

    render(<Select {...propsMock} />);

    expect(screen.queryByRole('button')).toHaveTextContent('Another option');
});
