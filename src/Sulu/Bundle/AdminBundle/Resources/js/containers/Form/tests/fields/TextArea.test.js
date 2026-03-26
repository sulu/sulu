// @flow
import React from 'react';
import log from 'loglevel';
import {act, render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import TextArea from '../../fields/TextArea';
import TextAreaComponent from '../../../../components/TextArea';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

jest.mock('../../../../components/TextArea', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function getLatestTextAreaProps() {
    const calls = (TextAreaComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass error correctly to TextArea component', () => {
    const formInspector = ({locale: undefined}: any);
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <TextArea
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
            value="xyz"
        />
    );

    expect(getLatestTextAreaProps().valid).toBe(false);
});

test('Pass props correctly to TextArea component', () => {
    const formInspector = ({locale: undefined}: any);
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );
    const textAreaProps = getLatestTextAreaProps();

    expect(textAreaProps.maxCharacters).toBe(undefined);
    expect(textAreaProps.valid).toBe(true);
    expect(textAreaProps.disabled).toBe(true);
});

test('Component correctly logs deprecated warning for max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
    };

    const formInspector = ({locale: undefined}: any);
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );
    const textAreaProps = getLatestTextAreaProps();

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(textAreaProps.maxCharacters).toBe(70);
    expect(textAreaProps.valid).toBe(true);
});

test('Component correctly chooses soft_max_length over max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '55',
        },
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
    };

    const formInspector = ({locale: undefined}: any);
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );
    const textAreaProps = getLatestTextAreaProps();

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));

    expect(textAreaProps.maxCharacters).toBe(70);
    expect(textAreaProps.valid).toBe(true);
});

test('Pass props correctly including soft_max_length to TextArea component', () => {
    const schemaOptions = {
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
    };

    const formInspector = ({locale: undefined}: any);
    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );
    const textAreaProps = getLatestTextAreaProps();

    expect(textAreaProps.maxCharacters).toBe(70);
    expect(textAreaProps.valid).toBe(true);
});

test('TextArea should call onFocus when the TextArea gets focus', () => {
    const formInspector = ({locale: undefined}: any);
    const focusSpy = jest.fn();

    render(
        <TextArea
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFocus={focusSpy}
        />
    );

    const target = new EventTarget();
    act(() => {
        getLatestTextAreaProps().onFocus({
            target,
        });
    });

    expect(focusSpy).toBeCalledWith(target);
});
