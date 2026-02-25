// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Search from '../Search';

jest.mock('../../../utils/Translator', () => ({
    translate(key) {
        return key;
    },
}));

test('The component should render collapsed', () => {
    render(<Search onSearch={jest.fn()} value={null} />);

    const input = screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'});

    expect(input).toHaveValue('');
    expect(input.closest('div')).toHaveClass('collapsed');
});

test('The component should render not collapsed when value is given', () => {
    render(<Search onSearch={jest.fn()} value="search-string" />);

    const input = screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'});

    expect(input).toHaveValue('search-string');
    expect(input.closest('div')).not.toHaveClass('collapsed');
});

test('The component should update the value if a new one is provided', () => {
    const onSearch = jest.fn();
    const {rerender} = render(<Search onSearch={onSearch} value="search-string" />);
    expect(screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'})).toHaveValue('search-string');

    rerender(<Search onSearch={onSearch} value="new-search-string" />);
    expect(screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'})).toHaveValue('new-search-string');
});

test('The component should expand the input when clicking on icon', async() => {
    const user = userEvent.setup();
    render(<Search onSearch={jest.fn()} value={null} />);
    const input = screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'});

    expect(input.closest('div')).toHaveClass('collapsed');
    await user.click(screen.getByRole('button', {name: 'su-search'}));
    expect(input.closest('div')).not.toHaveClass('collapsed');
});

test('The component should trigger the onSearch callback correctly if Input calls onBlur', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(<Search onSearch={onSearch} value={null} />);
    const input = screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'});

    await user.click(screen.getByRole('button', {name: 'su-search'}));
    await user.type(input, 'test-search-value');
    await user.tab();

    expect(onSearch).toBeCalledWith('test-search-value');
});

test('The component should trigger the onSearch callback correctly if Input calls onKeyPress with enter', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(<Search onSearch={onSearch} value={null} />);
    const input = screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'});

    await user.click(screen.getByRole('button', {name: 'su-search'}));
    await user.type(input, 'test-search-value{enter}');

    expect(onSearch).toBeCalledWith('test-search-value');
});

test('The component should clear the current value if Input calls onClearClick', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(<Search onSearch={onSearch} value={null} />);
    const input = screen.getByRole('textbox', {name: 'sulu_admin.list_search_placeholder'});

    await user.click(screen.getByRole('button', {name: 'su-search'}));
    await user.type(input, 'test-search-value');
    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(input).toHaveValue('');
    expect(input.closest('div')).toHaveClass('collapsed');
    expect(onSearch).toHaveBeenCalledWith(undefined);
});
