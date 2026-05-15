// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Select from '../Select';
import Option from '../Option';

jest.unmock('debounce');

const Divider = Select.Divider;

beforeEach(() => {
    jest.clearAllMocks();
});

function renderSelect(props: any = {}, children: any = undefined) {
    const defaultChildren = [
        <Option key="option-1" value="option-1">Option 1</Option>,
        <Option key="option-2" value="option-2">Option 2</Option>,
        <Divider key="divider" />,
        <Option key="option-3" value="option-3">Option 3</Option>,
    ];
    const renderedChildren = children && children.type === React.Fragment
        ? children.props.children
        : (children || defaultChildren);

    const view = render(
        <Select
            displayValue="My text"
            isOptionSelected={jest.fn().mockReturnValue(false)}
            onSelect={jest.fn()}
            {...props}
        >
            {renderedChildren}
        </Select>
    );

    return view;
}

function getDisplayButton(name: string = 'My text') {
    return screen.getByRole('button', {name: new RegExp(name)});
}

test('The component should render with a dark skin', async() => {
    const user = userEvent.setup();
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const {asFragment} = renderSelect({
        icon: 'su-plus',
        isOptionSelected,
        skin: 'dark',
    });

    const displayButton = getDisplayButton();
    Object.defineProperty((displayButton: any), 'getBoundingClientRect', {
        configurable: true,
        value: jest.fn(() => ({width: 200})),
    });

    await user.click(displayButton);

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByRole('list')).toMatchSnapshot();
});

test('The component should show a disabled select when disabled', () => {
    renderSelect({
        disabled: true,
        icon: 'su-plus',
    });

    expect(getDisplayButton()).toBeDisabled();
});

test('The component should not open the popover on display-value-click when disabled', async() => {
    const user = userEvent.setup();
    renderSelect({disabled: true});

    await user.click(getDisplayButton());

    expect(screen.queryByRole('list')).not.toBeInTheDocument();
});

test('The component should open the popover when pressing enter', async() => {
    const user = userEvent.setup();
    renderSelect();

    await user.tab();
    await user.keyboard('{Enter}');
    expect(screen.getByRole('list')).toBeInTheDocument();
});

test('The component should open the popover when pressing arrow down', async() => {
    const user = userEvent.setup();
    renderSelect();

    await user.tab();
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('list')).toBeInTheDocument();
});

test('The component should open the popover when pressing arrow up', async() => {
    const user = userEvent.setup();
    renderSelect();

    await user.tab();
    await user.keyboard('{ArrowUp}');
    expect(screen.getByRole('list')).toBeInTheDocument();
});

test('The component should trigger the select callback and close the popover when an option is clicked', async() => {
    const user = userEvent.setup();
    const onSelect = jest.fn();
    renderSelect({
        isOptionSelected: jest.fn().mockReturnValue(false),
        onSelect,
    });

    await user.click(getDisplayButton());
    await user.click(screen.getByRole('button', {name: 'Option 3'}));

    expect(onSelect).toHaveBeenCalledWith('option-3');
    expect(screen.queryByRole('list')).not.toBeInTheDocument();
});

test('The component should call the onClose callback when it is closing', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();
    renderSelect({
        onClose: closeSpy,
    }, [
        <Option key="option-1" value="option-1">Option 1</Option>,
        <Option key="option-2" value="option-2">Option 2</Option>,
    ]);

    await user.click(getDisplayButton());

    expect(closeSpy).not.toBeCalled();
    await user.click(screen.getByTestId('backdrop'));
    expect(closeSpy).toBeCalled();
});

test('The component should render the selected option in opened list', async() => {
    const user = userEvent.setup();
    const isOptionSelected = jest.fn((child) => child.props.value === 'option-3');
    renderSelect({
        isOptionSelected,
        onSelect: jest.fn(),
    });

    await user.tab();
    await user.keyboard('{ArrowDown}');
    expect(screen.getByRole('button', {name: /Option 3$/})).toHaveClass('selected');
});

test('The component should pass the selected property to the options', async() => {
    const user = userEvent.setup();
    renderSelect({
        isOptionSelected: jest.fn().mockReturnValue(true),
        onSelect: jest.fn(),
    });

    await user.click(getDisplayButton());

    expect(document.querySelectorAll('button.option.selected')).toHaveLength(3);
});

test('The component should react on arrow down/up to focus children', async() => {
    const user = userEvent.setup();
    renderSelect({
        isOptionSelected: jest.fn().mockReturnValue(false),
        onSelect: jest.fn(),
    });

    await user.click(getDisplayButton());

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
    expect(screen.queryByRole('list')).not.toBeInTheDocument();
});

test('The component should set the current selected element as first focused element', async() => {
    const user = userEvent.setup();
    const isOptionSelected = jest.fn((child) => child.props.value === 'option-2');
    renderSelect({
        isOptionSelected,
        onSelect: jest.fn(),
    }, [
        <Option key="option-1" value="option-1">Option 1</Option>,
        <Option key="option-2" value="option-2">Option 2</Option>,
        <Divider key="divider" />,
        <Option key="option-3" value="option-3">Option 3</Option>,
    ]);

    await user.click(getDisplayButton());
    await user.keyboard('{ArrowDown}');

    expect(screen.getByRole('button', {name: 'Option 3'})).toHaveFocus();
});

test('The component should focus children matching keyboard input', async() => {
    const user = userEvent.setup();
    renderSelect({
        isOptionSelected: jest.fn().mockReturnValue(false),
        onSelect: jest.fn(),
    }, [
        <Option key="option-abc" value="option-abc">ABC</Option>,
        <Option key="option-def" value="option-def">DEF</Option>,
        <Option key="option-ghi" value="option-ghi">GHI</Option>,
    ]);

    await user.click(getDisplayButton());

    await user.keyboard('d');
    expect(screen.getByRole('button', {name: 'DEF'})).toHaveFocus();

    await user.keyboard('e');
    expect(screen.getByRole('button', {name: 'DEF'})).toHaveFocus();

    await user.keyboard('a');
    expect(screen.getByRole('button', {name: 'DEF'})).toHaveFocus();

    await user.keyboard('{Escape}');
    await user.click(getDisplayButton());

    await user.keyboard('a');
    expect(screen.getByRole('button', {name: 'ABC'})).toHaveFocus();
});

test('The component should focus itself after closing option list', async() => {
    const user = userEvent.setup();
    renderSelect({
        isOptionSelected: jest.fn().mockReturnValue(false),
        onSelect: jest.fn(),
    }, [
        <Option key="option-abc" value="option-abc">ABC</Option>,
        <Option key="option-def" value="option-def">DEF</Option>,
        <Option key="option-ghi" value="option-ghi">GHI</Option>,
    ]);
    const displayButton = getDisplayButton();

    await user.click(displayButton);
    await user.keyboard('{ArrowDown}');

    await user.keyboard('{Escape}');
    expect(displayButton).toHaveFocus();
});

test('The component should not focus itself after closing already closed option list', async() => {
    const user = userEvent.setup();
    renderSelect({
        isOptionSelected: jest.fn().mockReturnValue(false),
        onSelect: jest.fn(),
    }, [
        <Option key="option-abc" value="option-abc">ABC</Option>,
        <Option key="option-def" value="option-def">DEF</Option>,
        <Option key="option-ghi" value="option-ghi">GHI</Option>,
    ]);
    const displayButton = getDisplayButton();
    const outsideButton = document.createElement('button');
    const body = document.body;
    if (!body) {
        throw new Error('Expected document body');
    }

    body.appendChild(outsideButton);
    outsideButton.focus();

    expect(outsideButton).toHaveFocus();
    await user.keyboard('{Escape}');
    expect(outsideButton).toHaveFocus();
    expect(displayButton).not.toHaveFocus();
    outsideButton.remove();
});
