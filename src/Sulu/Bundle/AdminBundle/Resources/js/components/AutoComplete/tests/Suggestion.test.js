// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import Suggestion from '../Suggestion';

test('Suggestion should render', () => {
    expect(render(
        <Suggestion
            icon="fa-ticket"
            onSelect={jest.fn()}
            value={{name: 'suggestion-1'}}
        >
            Suggestion 1
        </Suggestion>
    )).toMatchSnapshot();
});

test('Suggestion should render strong-tags around found chars', () => {
    expect(render(
        <Suggestion
            query="sug"
            icon="fa-ticket"
            onSelect={jest.fn()}
            value={{name: 'suggestion-1'}}
        >
            Suggestion 2
        </Suggestion>
    )).toMatchSnapshot();
});

test('Clicking on a suggestion should call the onClick handler', () => {
    const selectSpy = jest.fn();
    const suggestion = shallow(
        <Suggestion
            query="sug"
            icon="fa-ticket"
            value={{name: 'suggestion-1'}}
            onSelect={selectSpy}
        >
            {() => (
                <div>Suggestion 3</div>
            )}
        </Suggestion>
    );

    suggestion.find('button').simulate('click');
    expect(selectSpy).toHaveBeenCalledTimes(1);
});

test('Should highlight the part of the suggestion text which matches the query prop', () => {
    const suggestion = shallow(
        <Suggestion
            query="sug"
            icon="fa-ticket"
            onSelect={jest.fn()}
            value={{name: 'suggestion-1'}}
        >
            {(highlight) => (
                <div>{highlight('Suggestion 3')}</div>
            )}
        </Suggestion>
    );

    expect(suggestion.render()).toMatchSnapshot();
});
