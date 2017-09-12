/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import pretty from 'pretty';
import AutoComplete from '../AutoComplete';
import Suggestion from '../Suggestion';

afterEach(() => document.body.innerHTML = '');

test('AutoComplete should render', () => {
    expect(render(
        <AutoComplete>
            <Suggestion
                icon="ticket"
                value="suggestion-1"
            />
            <Suggestion
                icon="ticket"
                value="suggestion-2"
            />
            <Suggestion
                icon="ticket"
                value="suggestion-3"
            />
        </AutoComplete>
    )).toMatchSnapshot();
});

test('Render the AutoComplete with open suggestions list', () => {
    const autoComplete = mount(
        <AutoComplete>
            <Suggestion
                icon="ticket"
                value="suggestion-1"
            />
            <Suggestion
                icon="ticket"
                value="suggestion-2"
            />
            <Suggestion
                icon="ticket"
                value="suggestion-3"
            />
        </AutoComplete>
    );

    autoComplete.instance().openSuggestions();
    expect(autoComplete.render()).toMatchSnapshot();
    expect(pretty(document.body.innerHTML)).toMatchSnapshot();
});

test('Render the AutoComplete with the placeholder text', () => {
    const autoComplete = mount(
        <AutoComplete
            noSuggestionsMessage="Nothing found, sadface..."
        />
    );

    autoComplete.instance().openSuggestions();
    expect(autoComplete.render()).toMatchSnapshot();
    expect(pretty(document.body.innerHTML)).toMatchSnapshot();
});

test('The threshold check should return true or false based on the threshold property', () => {
    const testValue1 = 'abc';
    const testValue2 = 'abcd';
    const autoComplete = shallow(
        <AutoComplete
            threshold="4"
        />
    );

    expect(autoComplete.instance().hasReachedThreshold(testValue1)).toBe(false);
    expect(autoComplete.instance().hasReachedThreshold(testValue2)).toBe(true);
});

test('Clicking on a suggestion should close the AutoComplete list and call the onChange handler', () => {
    const onChangeSpy = jest.fn();
    const testValue = 'suggestion-1';
    const autoComplete = mount(
        <AutoComplete
            onChange={onChangeSpy}
        >
            <Suggestion
                icon="ticket"
                value={testValue}
            />
            <Suggestion
                icon="ticket"
                value="suggestion-2"
            />
            <Suggestion
                icon="ticket"
                value="suggestion-3"
            />
        </AutoComplete>
    );

    autoComplete.instance().openSuggestions();
    document.body.querySelector('.suggestion:first-child').click();
    expect(onChangeSpy).toHaveBeenCalledWith(testValue);
    expect(document.body.innerHTML).toBe('');
});
