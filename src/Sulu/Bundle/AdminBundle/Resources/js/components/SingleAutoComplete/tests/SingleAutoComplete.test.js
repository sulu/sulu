// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SingleAutoComplete from '../SingleAutoComplete';

jest.mock('debounce', () => jest.fn((callback) => {
    const debounced = (...args) => callback(...args);
    debounced.clear = jest.fn();

    return debounced;
}));

jest.mock('../../Input', () => {
    const React = require('react');

    return function InputMock({disabled, onBlur, onChange, onFocus, value}) {
        function handleChange(event) {
            onChange(event.currentTarget.value);
        }

        return React.createElement('input', {
            'data-testid': 'single-auto-complete-input',
            disabled,
            onBlur,
            onChange: handleChange,
            onFocus,
            value: value || '',
        });
    };
});

jest.mock('../../AutoCompletePopover', () => {
    const React = require('react');

    return function AutoCompletePopoverMock({onClose, onSelect, open, suggestions = []}) {
        function handleSelectClick(event) {
            const index = Number(event.currentTarget.dataset.index);
            onSelect(suggestions[index]);
        }

        const suggestionButtons = open
            ? suggestions.map((suggestion, index) => React.createElement(
                'button',
                {
                    'data-index': index,
                    key: index,
                    onClick: handleSelectClick,
                    type: 'button',
                },
                suggestion.name
            ))
            : [];

        return React.createElement(
            'div',
            {'data-open': open ? 'true' : 'false', 'data-testid': 'single-auto-complete-popover'},
            React.createElement('button', {onClick: onClose, type: 'button'}, 'close'),
            ...suggestionButtons
        );
    };
});

test('SingleAutoComplete should render with suggestions', async() => {
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];
    const user = userEvent.setup();

    const {asFragment} = render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    await user.click(screen.getByTestId('single-auto-complete-input'));

    expect(asFragment()).toMatchSnapshot();
    expect(screen.getByTestId('single-auto-complete-popover')).toMatchSnapshot();
});

test('SingleAutoComplete should be disabled when in disabled state', () => {
    const suggestions = [
        {name: 'Suggestion 1'},
        {name: 'Suggestion 2'},
        {name: 'Suggestion 3'},
    ];

    render(
        <SingleAutoComplete
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    expect(screen.getByTestId('single-auto-complete-input')).toBeDisabled();
});

test('Selecting suggestion should fire onChange callback and update value of Input with selected value', async() => {
    const changeSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];
    const user = userEvent.setup();

    render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    expect(screen.getByTestId('single-auto-complete-input')).toHaveValue('Test');

    await user.click(screen.getByTestId('single-auto-complete-input'));
    await user.click(screen.getByRole('button', {name: 'Suggestion 1'}));

    expect(screen.getByTestId('single-auto-complete-input')).toHaveValue('Suggestion 1');
    expect(changeSpy).toHaveBeenCalledWith(suggestions[0]);
});

test('Should call onChange with undefined if all characters are removed from input', async() => {
    const changeSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];
    const user = userEvent.setup();

    render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={suggestions}
            value={{name: 'Test'}}
        />
    );

    expect(screen.getByTestId('single-auto-complete-input')).toHaveValue('Test');
    await user.clear(screen.getByTestId('single-auto-complete-input'));

    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Should call the onFinish callback when the Input lost focus', async() => {
    const finishSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={finishSpy}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={[{id: 1, name: 'Suggestion 1'}]}
            value={{name: 'Test'}}
        />
    );

    await user.click(screen.getByTestId('single-auto-complete-input'));
    await user.tab();

    expect(finishSpy).toHaveBeenCalled();
});

test('Should update value of Input when the value prop is updated', () => {
    const {rerender} = render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={[{id: 1, name: 'Suggestion 1'}]}
            value={{name: 'Test'}}
        />
    );

    expect(screen.getByTestId('single-auto-complete-input')).toHaveValue('Test');

    rerender(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={jest.fn()}
            searchProperties={['name']}
            suggestions={[{id: 1, name: 'Suggestion 1'}]}
            value={{name: 'new value'}}
        />
    );

    expect(screen.getByTestId('single-auto-complete-input')).toHaveValue('new value');
});

test('Should fire onSearch callback and open popover when input field is focused', async() => {
    const searchSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={searchSpy}
            searchProperties={['name']}
            suggestions={[{id: 1, name: 'Suggestion 1'}]}
            value={{name: 'Test'}}
        />
    );

    expect(searchSpy).not.toHaveBeenCalled();
    expect(screen.getByTestId('single-auto-complete-popover')).toHaveAttribute('data-open', 'false');

    await user.click(screen.getByTestId('single-auto-complete-input'));

    expect(searchSpy).toHaveBeenCalledWith('Test');
    expect(screen.getByTestId('single-auto-complete-popover')).toHaveAttribute('data-open', 'true');
});

test('Should close popover when requested and reopen popover when input field is changed', async() => {
    const searchSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <SingleAutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            onFinish={jest.fn()}
            onSearch={searchSpy}
            searchProperties={['name']}
            suggestions={[{id: 1, name: 'Suggestion 1'}]}
            value={{name: 'Test'}}
        />
    );

    await user.click(screen.getByTestId('single-auto-complete-input'));
    expect(searchSpy).toHaveBeenNthCalledWith(1, 'Test');
    expect(screen.getByTestId('single-auto-complete-popover')).toHaveAttribute('data-open', 'true');

    await user.click(screen.getByRole('button', {name: 'close'}));
    expect(screen.getByTestId('single-auto-complete-popover')).toHaveAttribute('data-open', 'false');

    await user.type(screen.getByTestId('single-auto-complete-input'), 'search term');
    expect(searchSpy).toHaveBeenLastCalledWith('Testsearch term');
    expect(screen.getByTestId('single-auto-complete-popover')).toHaveAttribute('data-open', 'true');
});
