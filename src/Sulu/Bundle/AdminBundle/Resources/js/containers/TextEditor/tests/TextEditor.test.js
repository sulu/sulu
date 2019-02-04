// @flow
import React from 'react';
import {render, shallow, mount} from 'enzyme';
import TextEditor from '../TextEditor';
import textEditorRegistry from '../registries/TextEditorRegistry';

jest.mock('../registries/TextEditorRegistry', () => ({
    get: jest.fn(),
}));

test('Render the TextEditor', () => {
    textEditorRegistry.get.mockReturnValue(() => (<textarea />));

    expect(
        render(<TextEditor adapter="test" onBlur={jest.fn()} onChange={jest.fn()} options={{}} value={undefined} />)
    ).toMatchSnapshot();
});

test('Pass correct props to the given adapter', () => {
    class TestAdapter extends React.Component<{}> {
        render() {
            return null;
        }
    }
    textEditorRegistry.get.mockReturnValue(TestAdapter);

    const textEditor = mount(
        <TextEditor
            adapter="test"
            disabled={true}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={{}}
            value="testValue"
        />
    );

    expect(textEditor.find('TestAdapter').prop('value')).toEqual('testValue');
    expect(textEditor.find('TestAdapter').prop('disabled')).toEqual(true);
});

test('Throw an exception if a not existing adapter is used', () => {
    textEditorRegistry.get.mockImplementation((key) => {
        throw new Error(key);
    });

    expect(
        () => shallow(
            <TextEditor adapter="test" onBlur={jest.fn()} onChange={jest.fn()} options={{}} value={undefined} />
        )
    ).toThrow(/test/);
});
