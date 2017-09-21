/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render, shallow} from 'enzyme';
import Suggestion from '../Suggestion';

test('Suggestion should render', () => {
    expect(render(
        <Suggestion
            icon="ticket"
            value="suggestion-1"
        >
            Suggestion 1
        </Suggestion>
    )).toMatchSnapshot();
});

test('Suggestion should render strong-tags around found chars', () => {
    expect(render(
        <Suggestion
            query="sug"
            icon="ticket"
            value="suggestion-1"
        >
            Suggestion 2
        </Suggestion>
    )).toMatchSnapshot();
});

test('Clicking on a suggestion should call the onClick handler', () => {
    const onClickSpy = jest.fn();
    const suggestion = shallow(
        <Suggestion
            query="sug"
            icon="ticket"
            value="suggestion-1"
            onSelection={onClickSpy}
        >
            {() => (
                <div>Suggestion 3</div>
            )}
        </Suggestion>
    );

    suggestion.simulate('click');
    expect(onClickSpy).toHaveBeenCalledTimes(1);
});
