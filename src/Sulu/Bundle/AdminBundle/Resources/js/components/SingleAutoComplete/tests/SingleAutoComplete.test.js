// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import SingleAutoComplete from '../SingleAutoComplete';

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

beforeEach(() => {
    jest.clearAllMocks();
});

function renderSingleAutoComplete(props: any = {}) {
    return render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={[]}
            value={{name: 'Test'}}
            {...props}
        />
    );
}

function getInput() {
    return screen.getByRole('textbox');
}

test('SingleAutoComplete should render with suggestions', async() => {
    const user = userEvent.setup();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];
    const {asFragment} = renderSingleAutoComplete({suggestions});

    await user.click(getInput());

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByRole('list')).toMatchSnapshot();
});

test('SingleAutoComplete should be disabled when in disabled state', () => {
    const suggestions = [
        {name: 'Suggestion 1'},
        {name: 'Suggestion 2'},
        {name: 'Suggestion 3'},
    ];

    renderSingleAutoComplete({
        disabled: true,
        suggestions,
    });

    expect(getInput()).toBeDisabled();
});

test('Selecting suggestion should fire onChange callback and update value of Input with selected value', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    renderSingleAutoComplete({
        onChange: changeSpy,
        suggestions,
    });

    expect(getInput().value).toEqual('Test');

    await user.click(getInput());
    await user.click(screen.getByRole('button', {name: 'Suggestion 1'}));

    expect(getInput().value).toEqual('Suggestion 1');
    expect(changeSpy).toHaveBeenCalledWith(suggestions[0]);
});

test('Should call onChange with undefined if all characters are removed from input', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    renderSingleAutoComplete({
        onChange: changeSpy,
        suggestions,
    });

    expect(getInput().value).toEqual('Test');
    await user.clear(getInput());
    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Should call the onFinish callback when the Input lost focus', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderSingleAutoComplete({
        onFinish: finishSpy,
        suggestions,
    });

    await user.click(getInput());
    await user.tab();
    expect(finishSpy).toHaveBeenCalled();
});

test('Should update value of Input when the value prop is updated', () => {
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];
    const {rerender} = renderSingleAutoComplete({suggestions});

    expect(getInput().value).toEqual('Test');
    rerender(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'new value'}}
        />
    );
    expect(getInput().value).toEqual('new value');
});

test('Should fire onSearch callback and open popover when input field is focused', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderSingleAutoComplete({
        onSearch: searchSpy,
        suggestions,
    });

    expect(searchSpy).not.toHaveBeenCalled();
    expect(screen.queryByRole('list')).not.toBeInTheDocument();

    await user.click(getInput());
    expect(searchSpy).toHaveBeenCalledWith('Test');
    expect(screen.getByRole('list')).toBeInTheDocument();
});

test('Should close popover when requested and reopen popover when input field is changed', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderSingleAutoComplete({
        onSearch: searchSpy,
        suggestions,
    });

    await user.click(getInput());
    expect(searchSpy).toHaveBeenNthCalledWith(1, 'Test');
    expect(screen.getByRole('list')).toBeInTheDocument();

    await user.click(screen.getByTestId('backdrop'));
    expect(screen.queryByRole('list')).not.toBeInTheDocument();

    await user.clear(getInput());
    await user.type(getInput(), 'search term');
    expect(searchSpy).toHaveBeenLastCalledWith('search term');
    expect(screen.getByRole('list')).toBeInTheDocument();
});
