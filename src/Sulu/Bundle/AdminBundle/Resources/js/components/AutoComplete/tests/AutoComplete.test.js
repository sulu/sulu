/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, mount} from 'enzyme';
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
            >
                Suggestion 1
            </Suggestion>
            <Suggestion
                icon="ticket"
                value="suggestion-2"
            >
                Suggestion 2
            </Suggestion>
            <Suggestion
                icon="ticket"
                value="suggestion-3"
            >
                Suggestion 3
            </Suggestion>
        </AutoComplete>
    )).toMatchSnapshot();
});

test('Render the AutoComplete with open suggestions list', () => {
    const autoComplete = mount(
        <AutoComplete>
            <Suggestion
                icon="ticket"
                value="suggestion-1"
            >
                Suggestion 1
            </Suggestion>
            <Suggestion
                icon="ticket"
                value="suggestion-2"
            >
                Suggestion 2
            </Suggestion>
            <Suggestion
                icon="ticket"
                value="suggestion-3"
            >
                Suggestion 3
            </Suggestion>
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

test('Clicking on a suggestion should close the AutoComplete list and call the onSuggestionSelection handler', () => {
    const onSuggestionSelectionSpy = jest.fn();
    const testValue = 'suggestion-1';
    const autoComplete = mount(
        <AutoComplete
            onSuggestionSelection={onSuggestionSelectionSpy}
        >
            <Suggestion
                icon="ticket"
                value={testValue}
            >
                Suggestion 1
            </Suggestion>
            <Suggestion
                icon="ticket"
                value="suggestion-2"
            >
                Suggestion 2
            </Suggestion>
            <Suggestion
                icon="ticket"
                value="suggestion-3"
            >
                Suggestion 3
            </Suggestion>
        </AutoComplete>
    );

    autoComplete.instance().openSuggestions();
    document.body.querySelector('.suggestion:first-child').click();
    expect(onSuggestionSelectionSpy).toHaveBeenCalledWith(testValue);
    expect(document.body.innerHTML).toBe('');
});
