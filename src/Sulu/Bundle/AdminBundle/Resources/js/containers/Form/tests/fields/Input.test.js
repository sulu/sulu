// @flow
import React from 'react';
import log from 'loglevel';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Input from '../../fields/Input';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to Input component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Input
            {...createProps()}
            error={error}
        />
    );

    expect(screen.getByRole('textbox').closest('div')).toHaveClass('error');
});

test('Pass props correctly to Input component', () => {
    render(
        <Input
            {...createProps()}
            disabled={true}
        />
    );

    const input = screen.getByRole('textbox');
    expect(input).toBeDisabled();
    expect(input.closest('div')).not.toHaveClass('error');
    expect(input.closest('div')).not.toHaveClass('headline');
});

test('Pass headline prop correctly', () => {
    const schemaOptions = {
        headline: {
            name: 'headline',
            value: true,
        },
    };
    render(
        <Input
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );

    expect(screen.getByRole('textbox').closest('div')).toHaveClass('headline');
});

test('Component correctly logs deprecated warning for max_characters', () => {
    const schemaOptions = {
        max_characters: {
            name: 'max_characters',
            value: '70',
        },
    };
    render(
        <Input
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
        <Input
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );

    expect(log.warn).toBeCalledWith(expect.stringContaining('The "max_characters" schema option is deprecated'));
    expect(screen.getByText((content) => content.startsWith('70 '))).toBeInTheDocument();
});

test('Pass props correctly including soft_max_length to Input component', () => {
    const schemaOptions = {
        soft_max_length: {
            name: 'soft_max_length',
            value: '70',
        },
        max_segments: {
            name: 'max_segments',
            value: '6',
        },
        segment_delimiter: {
            name: 'segment_delimiter',
            value: ',',
        },
    };
    render(
        <Input
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );

    expect(screen.getByText((content) => content.startsWith('70 '))).toBeInTheDocument();
    expect(screen.getByText((content) => content.startsWith('6 '))).toBeInTheDocument();
});

test('Should not pass any arguments to onFinish callback', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();

    render(
        <Input
            {...createProps()}
            onFinish={finishSpy}
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.tab();

    expect(finishSpy).toBeCalledWith();
});

test('Input should call onFocus when the input gets focus', async() => {
    const user = userEvent.setup();
    const focusSpy = jest.fn();

    render(
        <Input
            {...createProps()}
            onFocus={focusSpy}
        />
    );

    const input = screen.getByRole('textbox');
    await user.click(input);

    expect(focusSpy).toBeCalledWith(input);
});
