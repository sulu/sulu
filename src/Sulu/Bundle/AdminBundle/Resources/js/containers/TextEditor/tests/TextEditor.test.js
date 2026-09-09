// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, shallow, mount} from 'enzyme';
import TextEditor from '../TextEditor';
import textEditorRegistry from '../registries/textEditorRegistry';
import textEditorConfigRegistry from '../registries/textEditorConfigRegistry';

jest.mock('../registries/textEditorRegistry', () => ({
    get: jest.fn(),
}));

const defaultConfig = {enterMode: 'p', features: ['align'], tags: ['strong', 'em']};

beforeEach(() => {
    textEditorConfigRegistry.clear();
    textEditorConfigRegistry.add('default', defaultConfig);
    textEditorConfigRegistry.add('mini', {enterMode: 'br', features: [], tags: ['a']});
});

test('Render the TextEditor', () => {
    textEditorRegistry.get.mockReturnValue(() => (<textarea />));

    expect(
        render(
            <TextEditor
                adapter="test"
                locale={undefined}
                onBlur={jest.fn()}
                onChange={jest.fn()}
                options={{}}
                value={undefined}
            />
        )
    ).toMatchSnapshot();
});

test('Pass correct props to the given adapter', () => {
    class TestAdapter extends React.Component<{}> {
        render() {
            return null;
        }
    }
    textEditorRegistry.get.mockReturnValue(TestAdapter);

    const locale = observable.box('en');

    const textEditor = mount(
        <TextEditor
            adapter="test"
            disabled={true}
            locale={locale}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={{}}
            value="testValue"
        />
    );

    expect(textEditor.find('TestAdapter').prop('disabled')).toEqual(true);
    expect(textEditor.find('TestAdapter').prop('locale')).toEqual(locale);
    expect(textEditor.find('TestAdapter').prop('value')).toEqual('testValue');
    expect(textEditor.find('TestAdapter').prop('config')).toEqual(defaultConfig);
});

test('Pass the config named by the config option to the given adapter', () => {
    class TestAdapter extends React.Component<{}> {
        render() {
            return null;
        }
    }
    textEditorRegistry.get.mockReturnValue(TestAdapter);

    const textEditor = mount(
        <TextEditor
            adapter="test"
            locale={undefined}
            onChange={jest.fn()}
            options={{config: {name: 'config', value: 'mini'}}}
            value={undefined}
        />
    );

    expect(textEditor.find('TestAdapter').prop('config'))
        .toEqual({enterMode: 'br', features: [], tags: ['a']});
});

test('Throw an exception if a not existing adapter is used', () => {
    textEditorRegistry.get.mockImplementation((key) => {
        throw new Error(key);
    });

    expect(
        () => shallow(
            <TextEditor
                adapter="test"
                locale={undefined}
                onBlur={jest.fn()}
                onChange={jest.fn()}
                options={{}}
                value={undefined}
            />
        )
    ).toThrow(/test/);
});
