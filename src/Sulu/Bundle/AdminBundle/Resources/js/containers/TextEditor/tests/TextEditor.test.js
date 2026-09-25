// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import {observable} from 'mobx';
import TextEditor from '../TextEditor';
import textEditorRegistry from '../registries/textEditorRegistry';

jest.mock('../registries/textEditorRegistry', () => ({
    get: jest.fn(),
}));

test('Render the TextEditor', () => {
    textEditorRegistry.get.mockReturnValue(() => (<textarea aria-label="editor" readOnly={true} />));

    render(
        <TextEditor
            adapter="test"
            locale={undefined}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={{}}
            value={undefined}
        />
    );

    expect(screen.getByLabelText('editor')).toBeInTheDocument();
});

test('Pass correct props to the given adapter', () => {
    let adapterProps: Object = {};

    function TestAdapter(props) {
        adapterProps = props;

        return <div data-testid="adapter" />;
    }

    textEditorRegistry.get.mockReturnValue(TestAdapter);

    const locale = observable.box('en');

    render(
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

    expect(screen.getByTestId('adapter')).toBeInTheDocument();
    expect(adapterProps.disabled).toEqual(true);
    expect(adapterProps.locale).toEqual(locale);
    expect(adapterProps.value).toEqual('testValue');
});

test('Throw an exception if a not existing adapter is used', () => {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    textEditorRegistry.get.mockImplementation((key) => {
        throw new Error(key);
    });

    expect(
        () => render(
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

    consoleErrorSpy.mockRestore();
});
