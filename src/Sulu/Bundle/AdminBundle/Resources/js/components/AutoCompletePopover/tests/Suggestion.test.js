// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Suggestion from '../Suggestion';

test('Suggestion should render', () => {
    const {container} = render(
        <Suggestion
            icon="fa-ticket"
            onSelect={jest.fn()}
            value={{name: 'suggestion-1'}}
        >
            Suggestion 1
        </Suggestion>
    );

    expect(container).toMatchSnapshot();
});

test('Suggestion should render strong-tags around found chars', () => {
    const {container} = render(
        <Suggestion
            icon="fa-ticket"
            onSelect={jest.fn()}
            query="sug"
            value={{name: 'suggestion-1'}}
        >
            Suggestion 2
        </Suggestion>
    );

    expect(container).toMatchSnapshot();
});

test('Suggestion should render if given query is not a valid regular expression', () => {
    const {container} = render(
        <Suggestion
            icon="fa-ticket"
            onSelect={jest.fn()}
            query="*+"
            value={{name: 'suggestion-1'}}
        >
            Suggestion 2
        </Suggestion>
    );

    expect(container).toMatchSnapshot();
});

test('Clicking on a suggestion should call the onClick handler', async() => {
    const selectSpy = jest.fn();
    render(
        <Suggestion
            icon="fa-ticket"
            onSelect={selectSpy}
            query="sug"
            value={{name: 'suggestion-1'}}
        >
            {() => (
                <div>Suggestion 3</div>
            )}
        </Suggestion>
    );

    const user = userEvent.setup();
    await user.click(screen.getByText('Suggestion 3'));

    expect(selectSpy).toHaveBeenCalledTimes(1);
});

test('Should highlight the part of the suggestion text which matches the query prop', () => {
    const {container} = render(
        <Suggestion
            icon="fa-ticket"
            onSelect={jest.fn()}
            query="sug"
            value={{name: 'suggestion-1'}}
        >
            {(highlight) => (
                <div>{highlight('Suggestion 3')}</div>
            )}
        </Suggestion>
    );

    expect(container).toMatchSnapshot();
});
