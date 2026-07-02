/* eslint-disable testing-library/prefer-user-event */
// @flow
import {fireEvent, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import React from 'react';
import MultiAutoComplete from '../MultiAutoComplete';

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

beforeEach(() => {
    Mousetrap.reset();
    jest.clearAllMocks();
});

function renderMultiAutoComplete(props: any = {}) {
    return render(
        <MultiAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={[]}
            value={[]}
            {...props}
        />
    );
}

function getInput() {
    return screen.getByRole('textbox');
}

function changeInputValue(value: string) {
    fireEvent.change(getInput(), {currentTarget: {value}, target: {value}});
}

test('Render the MultiAutoComplete with open suggestions list', () => {
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const {asFragment} = renderMultiAutoComplete({
        suggestions,
        value: [{id: 4, name: 'Test'}],
    });

    fireEvent.focus(getInput());

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByRole('list')).toMatchSnapshot();
});

test('MultiAutoComplete should be disabled in disabled state', () => {
    const suggestions = [
        {name: 'Suggestion 1'},
        {name: 'Suggestion 2'},
        {name: 'Suggestion 3'},
    ];
    const value = [
        {id: 1, name: 'Test'},
        {id: 2, name: 'Test 2'},
    ];

    renderMultiAutoComplete({
        disabled: true,
        suggestions,
        value,
    });

    expect(getInput()).toBeDisabled();
});

test('Should assign input as ref to inputRef', () => {
    const inputRefSpy = jest.fn();

    renderMultiAutoComplete({
        inputRef: inputRefSpy,
        searchProperties: [],
        suggestions: [],
        value: [],
    });

    expect(inputRefSpy).toHaveBeenCalledWith(getInput());
});

test('Clicking a suggestion should call onChange with value of the Suggestion and focus input afterwards', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];
    const value = [
        {id: 5, name: 'Test'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        suggestions,
        value,
    });

    fireEvent.focus(getInput());
    await user.click(screen.getByRole('button', {name: 'Suggestion 1'}));

    expect(changeSpy).toHaveBeenCalledWith([...value, suggestions[0]]);
    expect(getInput()).toHaveFocus();
});

test('Clicking delete icon should call onChange callback without the deleted Suggestion', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const value = [
        {id: 5, name: 'Test'},
        {id: 6, name: 'Test'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        suggestions: [],
        value,
    });

    const deleteButtons = screen.getAllByRole('button', {name: 'su-times'});
    await user.click(deleteButtons[1]);

    expect(changeSpy).toHaveBeenCalledWith([value[0]]);
});

test('Should call the onFinish callback when an item is added', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        onFinish: finishSpy,
        suggestions,
        value: [],
    });

    fireEvent.focus(getInput());
    await user.click(screen.getByRole('button', {name: 'Suggestion 1'}));

    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should not trigger any callbacks when input is not focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [],
    });

    changeInputValue('test');

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should trigger callbacks when input matches a suggestion and input is focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [],
    });

    changeInputValue('Suggestion 1');
    fireEvent.focus(getInput());

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).toHaveBeenCalledWith([suggestions[0]]);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should not trigger callbacks when input does not match a suggestion and input is focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [],
    });

    changeInputValue('Suggestion');
    fireEvent.focus(getInput());

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should not trigger callbacks when input matches a suggestion and input has lost focus', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [],
    });

    changeInputValue('Suggestion 1');
    fireEvent.focus(getInput());
    fireEvent.blur(getInput());

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should trigger callbacks when input does not match a suggestion and allowAdd is set', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [],
    });

    changeInputValue('Suggestion');
    fireEvent.focus(getInput());

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).toHaveBeenCalledWith([{name: 'Suggestion'}]);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should not trigger callbacks when input does not match a suggestion but an already added value', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [{name: 'Suggestion'}],
    });

    changeInputValue('Suggestion');
    fireEvent.focus(getInput());

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(getInput().value).toEqual('Suggestion');
    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should not trigger callbacks when input does not match case-insensitive an already added value', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [{name: 'Suggestion'}],
    });

    changeInputValue('suggestion');
    fireEvent.focus(getInput());

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(getInput().value).toEqual('suggestion');
    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should delete last value item if backspace is pressed in empty focused input field', () => {
    const changeSpy = jest.fn();
    const searchSpy = jest.fn();
    const finishSpy = jest.fn();

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        onSearch: searchSpy,
        suggestions: [],
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    fireEvent.focus(getInput());
    expect(searchSpy).toHaveBeenCalledTimes(1);
    expect(searchSpy).toHaveBeenNthCalledWith(1, '');

    Mousetrap.trigger('backspace');

    expect(searchSpy).toHaveBeenCalledTimes(2);
    expect(searchSpy).toHaveBeenNthCalledWith(2, '');
    expect(changeSpy).toHaveBeenCalledWith([{name: 'Tag1'}]);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should not delete last value item if backspace is pressed in filled focused input field', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions: [],
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    changeInputValue('Suggestion');
    fireEvent.focus(getInput());

    Mousetrap.trigger('backspace');

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should not delete last value item if backspace is pressed in empty non-focused input field', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions: [],
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    Mousetrap.trigger('backspace');

    expect(changeSpy).not.toHaveBeenCalled();
    expect(finishSpy).not.toHaveBeenCalled();
});

test('Should fire onSearch callback and open popover when input field is focused', () => {
    const searchSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        idProperty: 'name',
        onSearch: searchSpy,
        suggestions,
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    expect(searchSpy).not.toHaveBeenCalled();
    expect(screen.queryByRole('list')).not.toBeInTheDocument();

    fireEvent.focus(getInput());
    expect(searchSpy).toHaveBeenCalledWith('');
    expect(screen.getByRole('list')).toBeInTheDocument();
});

test('Should close popover when requested and reopen popover when input field is changed', async() => {
    const user = userEvent.setup();
    const searchSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        idProperty: 'name',
        onSearch: searchSpy,
        suggestions,
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    fireEvent.focus(getInput());
    expect(searchSpy).toHaveBeenNthCalledWith(1, '');
    expect(screen.getByRole('list')).toBeInTheDocument();

    await user.click(screen.getByTestId('backdrop'));
    expect(screen.queryByRole('list')).not.toBeInTheDocument();

    changeInputValue('search term');
    expect(searchSpy).toHaveBeenNthCalledWith(2, 'search term');
    expect(screen.getByRole('list')).toBeInTheDocument();
});
