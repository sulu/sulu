// @flow
import React from 'react';
import {render, mount} from 'enzyme';
import pretty from 'pretty';
import AutoComplete from '../AutoComplete';
import Suggestion from '../Suggestion';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

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
        <AutoComplete
            value="Test"
        >
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

    expect(autoComplete.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('Clicking on a suggestion should call the onChange handler with the value of the selected Suggestion', () => {
    const onChangeSpy = jest.fn();
    const testValue = 'suggestion-1';
    mount(
        <AutoComplete
            value="Test"
            onChange={onChangeSpy}
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

    document.body.querySelector('.suggestion:first-child').click();
    expect(onChangeSpy).toHaveBeenCalledWith(testValue);
});
