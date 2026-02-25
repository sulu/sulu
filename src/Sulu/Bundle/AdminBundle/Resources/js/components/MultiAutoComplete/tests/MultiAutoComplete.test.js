// @flow
/* eslint-disable react/jsx-no-bind */
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import MultiAutoComplete from '../MultiAutoComplete';
import AutoCompletePopover from '../../AutoCompletePopover';

jest.mock('debounce', () => jest.fn((callback) => {
    const debouncedFunction = (...parameters) => callback(...parameters);
    debouncedFunction.clear = jest.fn();

    return debouncedFunction;
}));

jest.mock('../../AutoCompletePopover', () => jest.fn((props) => {
    const {
        onClose,
        onSelect,
        open,
        suggestions,
    } = props;

    const handleClose = () => {
        if (onClose) {
            onClose();
        }
    };

    const SuggestionButton = ({suggestion}) => {
        const handleClick = () => {
            onSelect(suggestion);
        };

        return (
            <button onClick={handleClick} type="button">
                {suggestion.name}
            </button>
        );
    };

    return (
        <div data-open={open ? 'true' : 'false'} data-testid="auto-complete-popover">
            <button onClick={handleClose} type="button">Close suggestions</button>
            {open && suggestions.map((suggestion) => (
                <SuggestionButton key={suggestion.id || suggestion.name} suggestion={suggestion} />
            ))}
        </div>
    );
}));

jest.mock('../../Chip', () => jest.fn((props) => {
    const {
        children,
        disabled,
        onDelete,
        value,
    } = props;

    const handleClick = () => {
        onDelete(value);
    };

    return (
        <button disabled={disabled} onClick={handleClick} type="button">
            {children}
        </button>
    );
}));

const getLatestAutoCompletePopoverProps = () => getLatestMockProps((AutoCompletePopover: any));

const createProps = (overrides = {}): any => ({
    allowAdd: false,
    displayProperty: 'name',
    idProperty: 'id',
    onChange: jest.fn(),
    onFinish: jest.fn(),
    onSearch: jest.fn(),
    searchProperties: ['name'],
    suggestions: [],
    value: [],
    ...overrides,
});

const renderMultiAutoComplete = (overrides = {}) => {
    const ref = React.createRef();
    const view = render(<MultiAutoComplete {...createProps(overrides)} ref={ref} />);

    return {
        ...view,
        instance: (ref.current: any),
    };
};

beforeEach(() => {
    Mousetrap.reset();
    jest.clearAllMocks();
});

test('Render the MultiAutoComplete with open suggestions list', async() => {
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
        {id: 2, name: 'Suggestion 2'},
        {id: 3, name: 'Suggestion 3'},
    ];

    const {asFragment} = renderMultiAutoComplete({
        suggestions,
        value: [{id: 4, name: 'Test'}],
    });

    await userEvent.click(screen.getByRole('textbox'));

    expect(asFragment()).toMatchSnapshot();
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

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Should assign input as ref to inputRef', () => {
    const inputRefSpy = jest.fn();

    renderMultiAutoComplete({
        inputRef: inputRefSpy,
    });

    expect(inputRefSpy).toBeCalledWith(screen.getByRole('textbox'));
});

test('Clicking a suggestion should call onChange with value and focus input afterwards', async() => {
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

    await userEvent.click(screen.getByRole('textbox'));
    await userEvent.click(screen.getByRole('button', {name: 'Suggestion 1'}));

    expect(changeSpy).toHaveBeenCalledWith([...value, suggestions[0]]);
    expect(screen.getByRole('textbox')).toHaveFocus();
});

test('Clicking on delete icon of a suggestion should call onChange without deleted suggestion', async() => {
    const changeSpy = jest.fn();

    const value = [
        {id: 5, name: 'Test'},
        {id: 6, name: 'Test'},
    ];

    renderMultiAutoComplete({
        onChange: changeSpy,
        value,
    });

    await userEvent.click(screen.getAllByRole('button', {name: 'Test'})[1]);

    expect(changeSpy).toHaveBeenCalledWith([value[0]]);
});

test('Should call the onFinish callback when an item is added', async() => {
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    renderMultiAutoComplete({
        onFinish: finishSpy,
        suggestions,
    });

    await userEvent.click(screen.getByRole('textbox'));
    await userEvent.click(screen.getByRole('button', {name: 'Suggestion 1'}));

    expect(finishSpy).toBeCalledWith();
});

test('Should not trigger any callbacks when input is not focused', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
    });

    act(() => {
        instance.inputValue = 'test';
    });

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should trigger callbacks when input matches a suggestion and input is focused', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
    });

    act(() => {
        instance.inputValue = 'Suggestion 1';
    });

    await userEvent.click(screen.getByRole('textbox'));

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).toBeCalledWith([suggestions[0]]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not trigger callbacks when input does not match a suggestion and input is focused', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
    });

    act(() => {
        instance.inputValue = 'Suggestion';
    });

    await userEvent.click(screen.getByRole('textbox'));

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should not trigger callbacks when input matches a suggestion and input has lost focus', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
    });

    act(() => {
        instance.inputValue = 'Suggestion 1';
    });

    await userEvent.click(screen.getByRole('textbox'));
    await userEvent.tab();

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should trigger callbacks when input does not match a suggestion and allowAdd is set', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
    });

    act(() => {
        instance.inputValue = 'Suggestion';
    });

    await userEvent.click(screen.getByRole('textbox'));

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(changeSpy).toBeCalledWith([{name: 'Suggestion'}]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not trigger callbacks when input does not match a suggestion but an already added value', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [{name: 'Suggestion'}],
    });

    act(() => {
        instance.inputValue = 'Suggestion';
    });

    await userEvent.click(screen.getByRole('textbox'));

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(instance.inputValue).toEqual('Suggestion');
    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should not trigger callbacks when input does not match case-insensitive an already added value', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const suggestions = [
        {id: 1, name: 'Suggestion 1'},
    ];

    const {instance} = renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        suggestions,
        value: [{name: 'Suggestion'}],
    });

    act(() => {
        instance.inputValue = 'suggestion';
    });

    await userEvent.click(screen.getByRole('textbox'));

    Mousetrap.trigger('enter');
    Mousetrap.trigger(',');

    expect(instance.inputValue).toEqual('suggestion');
    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should delete last value item if backspace is pressed in empty focused input field', async() => {
    const changeSpy = jest.fn();
    const searchSpy = jest.fn();
    const finishSpy = jest.fn();

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        onSearch: searchSpy,
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    await userEvent.click(screen.getByRole('textbox'));
    expect(searchSpy).toBeCalledTimes(1);
    expect(searchSpy).nthCalledWith(1, '');

    Mousetrap.trigger('backspace');

    expect(searchSpy).toBeCalledTimes(2);
    expect(searchSpy).nthCalledWith(2, '');
    expect(changeSpy).toBeCalledWith([{name: 'Tag1'}]);
    expect(finishSpy).toBeCalledWith();
});

test('Should not delete last value item if backspace is pressed in filled focused input field', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const {instance} = renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    act(() => {
        instance.inputValue = 'Suggestion';
    });

    await userEvent.click(screen.getByRole('textbox'));

    Mousetrap.trigger('backspace');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should not delete last value item if backspace is pressed in empty non-focused input field', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderMultiAutoComplete({
        allowAdd: true,
        idProperty: 'name',
        onChange: changeSpy,
        onFinish: finishSpy,
        value: [{name: 'Tag1'}, {name: 'Tag2'}],
    });

    Mousetrap.trigger('backspace');

    expect(changeSpy).not.toBeCalled();
    expect(finishSpy).not.toBeCalled();
});

test('Should fire onSearch callback and open popover when input field is focused', async() => {
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

    expect(searchSpy).not.toBeCalled();
    expect(getLatestAutoCompletePopoverProps().open).toEqual(false);

    await userEvent.click(screen.getByRole('textbox'));

    expect(searchSpy).toBeCalledWith('');
    expect(getLatestAutoCompletePopoverProps().open).toEqual(true);
});

test('Should close popover when requested and reopen popover when input field is changed', async() => {
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

    await userEvent.click(screen.getByRole('textbox'));

    expect(searchSpy).nthCalledWith(1, '');
    expect(getLatestAutoCompletePopoverProps().open).toEqual(true);

    act(() => {
        getLatestAutoCompletePopoverProps().onClose();
    });

    expect(getLatestAutoCompletePopoverProps().open).toEqual(false);

    await userEvent.type(screen.getByRole('textbox'), 'search term');

    expect(searchSpy).toHaveBeenLastCalledWith('search term');
    expect(getLatestAutoCompletePopoverProps().open).toEqual(true);
});
