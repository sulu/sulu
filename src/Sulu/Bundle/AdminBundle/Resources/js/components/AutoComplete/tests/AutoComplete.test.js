// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import pretty from 'pretty';
import AutoComplete from '../AutoComplete';
import Suggestion from '../Suggestion';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('AutoComplete should render', () => {
    const changeSpy = jest.fn();
    const searchSpy = jest.fn();
    expect(render(
        <AutoComplete
            value="Test"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={searchSpy}
        >
            <Suggestion
                icon="fa-ticket"
                value="suggestion-1"
            >
                Suggestion 1
            </Suggestion>
            <Suggestion
                icon="fa-ticket"
                value="suggestion-2"
            >
                Suggestion 2
            </Suggestion>
            <Suggestion
                icon="fa-ticket"
                value="suggestion-3"
            >
                Suggestion 3
            </Suggestion>
        </AutoComplete>
    )).toMatchSnapshot();
});

test('Render the AutoComplete with open suggestions list', () => {
    const searchSpy = jest.fn();
    const changeSpy = jest.fn();
    const autoComplete = mount(
        <AutoComplete
            value="Test"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={searchSpy}
        >
            <Suggestion
                icon="fa-ticket"
                value="suggestion-1"
            >
                Suggestion 1
            </Suggestion>
            <Suggestion
                icon="fa-ticket"
                value="suggestion-2"
            >
                Suggestion 2
            </Suggestion>
            <Suggestion
                icon="fa-ticket"
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
    const changeSpy = jest.fn();
    const searchSpy = jest.fn();
    const testValue = 'suggestion-1';
    mount(
        <AutoComplete
            value="Test"
            onChange={changeSpy}
            onFinish={jest.fn()}
            onSearch={searchSpy}
        >
            <Suggestion
                icon="fa-ticket"
                value={testValue}
            >
                Suggestion 1
            </Suggestion>
            <Suggestion
                icon="fa-ticket"
                value="suggestion-2"
            >
                Suggestion 2
            </Suggestion>
            <Suggestion
                icon="fa-ticket"
                value="suggestion-3"
            >
                Suggestion 3
            </Suggestion>
        </AutoComplete>
    );

    const suggestion = document.body ? document.body.querySelector('.suggestion:first-child') : null;

    if (suggestion) {
        suggestion.click();
    }

    expect(changeSpy).toHaveBeenCalledWith(testValue);
});

test('Should call the onFinish callback when the Input lost focus', () => {
    const finishSpy = jest.fn();
    const autoComplete = shallow(
        <AutoComplete
            value="Test"
            onChange={jest.fn()}
            onFinish={finishSpy}
            onSearch={jest.fn()}
        >
            <Suggestion value="suggestion-1">
                Suggestion 1
            </Suggestion>
        </AutoComplete>
    );

    autoComplete.find('Input').simulate('finish');

    expect(finishSpy).toBeCalledWith();
});
