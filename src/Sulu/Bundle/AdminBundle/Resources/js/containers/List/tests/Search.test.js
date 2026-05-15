// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import bindValueToOnChange from '../../../utils/TestHelper/bindValueToOnChange';
import Search from '../Search';

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
        return key;
    },
}));

test('The component should render collapsed', () => {
    render(
        <Search onSearch={jest.fn()} value={null} />
    );

    const input = screen.getByRole('textbox', {hidden: true});
    expect(input.parentElement).toHaveClass('collapsed');
});

test('The component should render not collapsed when value is given', () => {
    render(
        <Search onSearch={jest.fn()} value="search-string" />
    );

    const input = screen.getByRole('textbox');
    expect(input.parentElement).not.toHaveAttribute('class', expect.stringContaining('collapsed'));
    expect(input).toHaveValue('search-string');
});

test('The component should update the value if a new one is provided', () => {
    const {rerender} = render(
        <Search onSearch={jest.fn()} value="search-string" />
    );

    expect(screen.getByRole('textbox')).toHaveValue('search-string');

    rerender(<Search onSearch={jest.fn()} value="new-search-string" />);

    expect(screen.getByRole('textbox')).toHaveValue('new-search-string');
});

test('The component should expand the input when clicking on icon', async() => {
    const user = userEvent.setup();
    render(
        <Search onSearch={jest.fn()} value={null} />
    );

    const input = screen.getByRole('textbox', {hidden: true});
    expect(input.parentElement).toHaveClass('collapsed');

    const searchIcon = screen.getByLabelText('su-search');
    await user.click(searchIcon);

    expect(input.parentElement).not.toHaveAttribute('class', expect.stringContaining('collapsed'));
});

test('The component should trigger the onSearch callback correctly if Input calls onBlur', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(
        bindValueToOnChange(<Search onSearch={onSearch} value={null} />)
    );

    const searchIcon = screen.getByLabelText('su-search');
    await user.click(searchIcon);

    const input = screen.getByRole('textbox');
    await user.type(input, 'test-search-value');
    await user.tab(); // Trigger onBlur

    expect(onSearch).toBeCalledWith('test-search-value');
});

test('The component should trigger the onSearch callback correctly if Input calls onKeyPress with enter', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(
        bindValueToOnChange(<Search onSearch={onSearch} value={null} />)
    );

    const searchIcon = screen.getByLabelText('su-search');
    await user.click(searchIcon);

    const input = screen.getByRole('textbox');
    await user.type(input, 'test-search-value{Enter}');

    expect(onSearch).toBeCalledWith('test-search-value');
});

test('The component should clear the current value if Input calls onClearClick', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(
        bindValueToOnChange(<Search onSearch={onSearch} value="test-search-value" />)
    );

    const clearIcon = screen.getByLabelText('su-times');
    await user.click(clearIcon);

    const input = screen.getByRole('textbox', {hidden: true});
    expect(input).toHaveValue('');
    expect(input.parentElement).toHaveClass('collapsed');
    expect(onSearch).toHaveBeenCalledWith(undefined);
});
