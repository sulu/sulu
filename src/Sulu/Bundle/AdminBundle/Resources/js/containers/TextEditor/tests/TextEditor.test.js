// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import TextEditor from '../TextEditor';
import textEditorRegistry from '../registries/TextEditorRegistry';

jest.mock('../registries/TextEditorRegistry', () => ({
    get: jest.fn(),
}));

test('Render the TextEditor', () => {
    textEditorRegistry.get.mockReturnValue(() => (<textarea />));
    expect(render(<TextEditor adapter="test" onChange={jest.fn()} value={undefined} />)).toMatchSnapshot();
});

test('Throw an exception if a not existing adapter is used', () => {
    textEditorRegistry.get.mockImplementation((key) => {
        throw new Error(key);
    });
    expect(() => shallow(<TextEditor adapter="test" onChange={jest.fn()} value={undefined} />)).toThrow(/test/);
});
