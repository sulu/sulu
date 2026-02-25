// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import TextEditor from '../TextEditor';
import textEditorRegistry from '../registries/textEditorRegistry';

jest.mock('../registries/textEditorRegistry', () => ({
    get: jest.fn(),
}));

test('Render the TextEditor', () => {
    const TestAdapter = jest.fn(() => null);
    textEditorRegistry.get.mockReturnValue(TestAdapter);

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

    expect((TestAdapter: any)).toBeCalled();
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

    const textEditorAdapterProps: any = getLatestMockProps((TestAdapter: any));
    expect(textEditorAdapterProps.disabled).toEqual(true);
    expect(textEditorAdapterProps.locale).toEqual(locale);
    expect(textEditorAdapterProps.value).toEqual('testValue');
});

test('Throw an exception if a not existing adapter is used', () => {
    textEditorRegistry.get.mockImplementation((key) => {
        throw new Error(key);
    });

    expect(() => render(
        <TextEditor
            adapter="test"
            locale={undefined}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            options={{}}
            value={undefined}
        />
    )).toThrow(/test/);
});
