// @flow
import React from 'react';
import log from 'loglevel';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import TextArea from '../../fields/TextArea';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to TextArea component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <TextArea
            {...createProps()}
            error={error}
            value="xyz"
        />
    );

    expect(screen.getByRole('textbox')).toHaveClass('error');
});

test('Pass props correctly to TextArea component', () => {
    render(
        <TextArea
            {...createProps()}
            disabled={true}
        />
    );

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Component correctly logs deprecated warning for max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
    };
    render(
        <TextArea
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));
    expect(screen.getByText((content) => content.startsWith('70 '))).toBeInTheDocument();
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
    render(
        <TextArea
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));
    expect(screen.getByText((content) => content.startsWith('70 '))).toBeInTheDocument();
});

test('Pass props correctly including soft_max_length to TextArea component', () => {
    const schemaOptions = {
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
    };
    render(
        <TextArea
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );

    expect(screen.getByText((content) => content.startsWith('70 '))).toBeInTheDocument();
});

test('TextArea should call onFocus when the TextArea gets focus', async() => {
    const user = userEvent.setup();
    const focusSpy = jest.fn();

    render(
        <TextArea
            {...createProps()}
            onFocus={focusSpy}
        />
    );

    const textArea = screen.getByRole('textbox');
    await user.click(textArea);

    expect(focusSpy).toBeCalledWith(textArea);
});
