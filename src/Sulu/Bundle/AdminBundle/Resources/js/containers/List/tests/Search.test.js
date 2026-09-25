// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Search from '../Search';

jest.mock('../../../utils/Translator');

function getInput() {
    return screen.getByPlaceholderText('sulu_admin.list_search_placeholder');
}

function getInputContainer() {
    return getInput().closest('.input');
}

test('The component should render collapsed', () => {
    render(<Search onSearch={jest.fn()} value={null} />);

    expect(getInput()).toHaveValue('');
    expect(getInputContainer()).toHaveClass('collapsed');
});

test('The component should render not collapsed when value is given', () => {
    render(<Search onSearch={jest.fn()} value="search-string" />);

    expect(getInput()).toHaveValue('search-string');
    expect(getInputContainer()).not.toHaveClass('collapsed');
    expect(screen.getByLabelText('su-times')).toBeInTheDocument();
});

test('The component should update the value if a new one is provided', () => {
    const {rerender} = render(<Search onSearch={jest.fn()} value="search-string" />);

    expect(getInput()).toHaveValue('search-string');

    rerender(<Search onSearch={jest.fn()} value="new-search-string" />);

    expect(getInput()).toHaveValue('new-search-string');
});

test('The component should expand the input when clicking on icon', async() => {
    const user = userEvent.setup();
    render(<Search onSearch={jest.fn()} value={null} />);

    await user.click(screen.getByLabelText('su-search'));

    expect(getInputContainer()).not.toHaveClass('collapsed');
});

test('The component should trigger the onSearch callback correctly if Input calls onBlur', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(<Search onSearch={onSearch} value={null} />);

    await user.click(screen.getByLabelText('su-search'));
    await user.type(getInput(), 'test-search-value');

    fireEvent.blur(getInput());

    expect(onSearch).toHaveBeenCalledWith('test-search-value');
});

test('The component should trigger the onSearch callback correctly if Input calls onKeyPress with enter', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(<Search onSearch={onSearch} value={null} />);

    await user.click(screen.getByLabelText('su-search'));
    await user.type(getInput(), 'test-search-value');

    await user.keyboard('{Enter}');

    expect(onSearch).toHaveBeenCalledWith('test-search-value');
});

test('The component should clear the current value if Input calls onClearClick', async() => {
    const user = userEvent.setup();
    const onSearch = jest.fn();
    render(<Search onSearch={onSearch} value={null} />);

    await user.click(screen.getByLabelText('su-search'));
    await user.type(getInput(), 'test-search-value');
    await user.click(screen.getByLabelText('su-times'));

    expect(getInput()).toHaveValue('');
    expect(getInputContainer()).toHaveClass('collapsed');
    expect(onSearch).toHaveBeenCalledWith(undefined);
});
