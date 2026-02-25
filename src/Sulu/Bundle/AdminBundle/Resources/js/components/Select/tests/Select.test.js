// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Select from '../Select';
import Option from '../Option';

const Divider = Select.Divider;

beforeEach(() => {
    jest.clearAllMocks();
});

test('The component should render with a dark skin', async() => {
    const user = userEvent.setup();
    const isOptionSelected = jest.fn().mockReturnValue(false);

    const {asFragment} = render(
        <Select
            displayValue="My text"
            icon="su-plus"
            isOptionSelected={isOptionSelected}
            onSelect={jest.fn()}
            skin="dark"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    expect(asFragment()).toMatchSnapshot();
});

test('The component should show a disabled select when disabled', () => {
    render(
        <Select
            disabled={true}
            displayValue="My text"
            icon="su-plus"
            isOptionSelected={jest.fn().mockReturnValue(false)}
            onSelect={jest.fn()}
        >
            <Option value="option-1">Option 1</Option>
        </Select>
    );

    expect(screen.getByRole('button', {name: /My text/i})).toBeDisabled();
});

test('The component should not open the popover on display-value-click when disabled', async() => {
    const user = userEvent.setup();
    render(
        <Select
            disabled={true}
            displayValue="My text"
            isOptionSelected={jest.fn().mockReturnValue(false)}
            onSelect={jest.fn()}
        >
            <Option value="option-1">Option 1</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    expect(screen.queryByRole('button', {name: 'Option 1'})).not.toBeInTheDocument();
});

test('The component should open the popover on Enter/ArrowDown/ArrowUp', async() => {
    const user = userEvent.setup();
    render(
        <Select
            displayValue="My text"
            isOptionSelected={jest.fn().mockReturnValue(false)}
            onSelect={jest.fn()}
        >
            <Option value="option-1">Option 1</Option>
        </Select>
    );
    await user.click(screen.getByRole('button', {name: /My text/i}));
    await user.keyboard('{Escape}');
    expect(screen.queryByRole('button', {name: 'Option 1'})).not.toBeInTheDocument();
    await user.keyboard('{Enter}');
    expect(screen.getByRole('button', {name: 'Option 1'})).toBeInTheDocument();

    await user.keyboard('{Escape}');
    expect(screen.queryByRole('button', {name: 'Option 1'})).not.toBeInTheDocument();
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: 'Option 1'})).toBeInTheDocument();

    await user.keyboard('{Escape}');
    expect(screen.queryByRole('button', {name: 'Option 1'})).not.toBeInTheDocument();
    await user.keyboard('{ArrowUp}');
    expect(screen.getByRole('button', {name: 'Option 1'})).toBeInTheDocument();
});

test('The component should trigger select callback and close popover when option is clicked', async() => {
    const user = userEvent.setup();
    const onSelect = jest.fn();
    render(
        <Select displayValue="My text" isOptionSelected={jest.fn().mockReturnValue(false)} onSelect={onSelect}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    await user.click(screen.getByRole('button', {name: 'Option 3'}));

    expect(onSelect).toHaveBeenCalledWith('option-3');
    expect(screen.queryByRole('button', {name: 'Option 3'})).not.toBeInTheDocument();
});

test('The component should call onClose callback when closing', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();
    render(
        <Select
            displayValue="My text"
            isOptionSelected={jest.fn().mockReturnValue(false)}
            onClose={closeSpy}
            onSelect={jest.fn()}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    expect(closeSpy).not.toBeCalled();
    await user.keyboard('{Escape}');
    expect(closeSpy).toBeCalled();
});

test('The component should focus selected option when opening', async() => {
    const user = userEvent.setup();
    const isOptionSelected = jest.fn((child) => child.props.value === 'option-3');
    render(
        <Select displayValue="My text" isOptionSelected={isOptionSelected} onSelect={jest.fn()}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    expect(screen.getByRole('button', {name: /Option 3/})).toHaveFocus();
});

test('The component should pass selected property to options', async() => {
    const user = userEvent.setup();
    render(
        <Select displayValue="My text" isOptionSelected={jest.fn().mockReturnValue(true)} onSelect={jest.fn()}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    expect(screen.getAllByLabelText('su-check')).toHaveLength(3);
});

test('The component should react on arrow down/up to focus children', async() => {
    const user = userEvent.setup();
    render(
        <Select displayValue="My text" isOptionSelected={jest.fn().mockReturnValue(false)} onSelect={jest.fn()}>
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));

    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: 'Option 1'})).toHaveFocus();
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: 'Option 2'})).toHaveFocus();
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: 'Option 3'})).toHaveFocus();
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: 'Option 3'})).toHaveFocus();

    await user.keyboard('{ArrowUp}');
    expect(screen.getByRole('button', {name: 'Option 2'})).toHaveFocus();
    await user.keyboard('{ArrowUp}');
    expect(screen.getByRole('button', {name: 'Option 1'})).toHaveFocus();
    await user.keyboard('{ArrowUp}');
    expect(screen.getByRole('button', {name: 'Option 1'})).toHaveFocus();

    await user.keyboard('{Escape}');
    expect(screen.queryByRole('button', {name: 'Option 1'})).not.toBeInTheDocument();
});

test('The component should set current selected element as first focused element', async() => {
    const user = userEvent.setup();
    render(
        <Select
            displayValue="My text"
            isOptionSelected={jest.fn((child) => child.props.value === 'option-2')}
            onSelect={jest.fn()}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    await user.click(screen.getByRole('button', {name: /My text/i}));
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: 'Option 3'})).toHaveFocus();
});

test('The component should focus children matching keyboard input', async() => {
    const user = userEvent.setup();
    render(
        <Select displayValue="My text" isOptionSelected={jest.fn().mockReturnValue(false)} onSelect={jest.fn()}>
            <Option value="option-abc">ABC</Option>
            <Option value="option-def">DEF</Option>
            <Option value="option-ghi">GHI</Option>
        </Select>
    );

    await user.click(screen.getByRole('button', {name: /My text/i}));
    await user.keyboard('d');
    expect(screen.getByRole('button', {name: 'DEF'})).toHaveFocus();
    await user.keyboard('e');
    expect(screen.getByRole('button', {name: 'DEF'})).toHaveFocus();
    await user.keyboard('a');
    expect(screen.getByRole('button', {name: 'DEF'})).toHaveFocus();
});

test('The component should focus itself after closing option list with Escape', async() => {
    const user = userEvent.setup();
    render(
        <Select displayValue="My text" isOptionSelected={jest.fn().mockReturnValue(false)} onSelect={jest.fn()}>
            <Option value="option-abc">ABC</Option>
            <Option value="option-def">DEF</Option>
            <Option value="option-ghi">GHI</Option>
        </Select>
    );

    const displayButton = screen.getByRole('button', {name: /My text/i});
    const focusSpy = jest.spyOn(displayButton, 'focus');

    await user.click(displayButton);
    focusSpy.mockClear();
    await user.keyboard('{Escape}');
    expect(focusSpy).toBeCalledTimes(1);
});

test('The component should not focus itself after closing an already closed list', async() => {
    const user = userEvent.setup();
    render(
        <Select displayValue="My text" isOptionSelected={jest.fn().mockReturnValue(false)} onSelect={jest.fn()}>
            <Option value="option-abc">ABC</Option>
            <Option value="option-def">DEF</Option>
            <Option value="option-ghi">GHI</Option>
        </Select>
    );

    const displayButton = screen.getByRole('button', {name: /My text/i});
    const focusSpy = jest.spyOn(displayButton, 'focus');

    await user.keyboard('{Escape}');
    expect(focusSpy).toBeCalledTimes(0);
});
