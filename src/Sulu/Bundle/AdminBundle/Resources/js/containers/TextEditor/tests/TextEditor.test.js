// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import TextEditor from '../TextEditor';
import textEditorRegistry from '../registries/textEditorRegistry';

jest.mock('../registries/textEditorRegistry', () => ({
    get: jest.fn(),
}));

test('Render the TextEditor', () => {
    textEditorRegistry.get.mockReturnValue(() => (<textarea />));

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

    expect(screen.getByRole('textbox')).toBeInTheDocument();
});

test('Pass correct props to the given adapter', () => {
    const TestAdapter = jest.fn(() => null);
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
    const testAdapterProps = TestAdapter.mock.calls[0][0];

    expect(testAdapterProps.disabled).toEqual(true);
    expect(testAdapterProps.locale).toEqual(locale);
    expect(testAdapterProps.value).toEqual('testValue');
});

test('Throw an exception if a not existing adapter is used', () => {
    textEditorRegistry.get.mockImplementation((key) => {
        throw new Error(key);
    });
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => undefined);

    try {
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
    } finally {
        consoleErrorSpy.mockRestore();
    }
});
