// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Url from '../../fields/Url';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error prop correctly to Url component', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Url
            {...createProps()}
            error={error}
            schemaOptions={schemaOptions}
        />
    );

    expect(screen.getByRole('textbox').parentElement).toHaveClass('error');
});

test('Pass props correctly to Url component', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    render(
        <Url
            {...createProps()}
            disabled={true}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(screen.getByRole('textbox')).toHaveValue('www.sulu.io');
    expect(screen.getByRole('textbox')).toBeDisabled();
    expect(screen.getByText('http://')).toBeInTheDocument();
});

test('Pass no schemaOptions to Url component and render correct defaults', async() => {
    const user = userEvent.setup();
    const schemaOptions = {};

    render(
        <Url
            {...createProps()}
            disabled={false}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(screen.getByRole('textbox')).toHaveValue('www.sulu.io');

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getAllByText('http://').length).toBeGreaterThan(0);
    expect(screen.getByRole('button', {name: 'https://'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'ftp://'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'ftps://'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'mailto:'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'tel:'})).toBeInTheDocument();
});

test('Not call changed when only protocol is given', () => {
    const schemaOptions = {
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'http://'},
            ],
        },
    };

    const changeSpy = jest.fn();

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Pass correct default props to Url component', () => {
    const schemaOptions = {
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'http://'},
                {name: 'specific_part', value: 'github.com'},
            ],
        },
    };
    const changeSpy = jest.fn();

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith('http://github.com', {'isDefaultValue': true});
});

test('Throw error if only specific_part default is set', () => {
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            name: 'defaults',
            value: [
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <Url
                {...createProps()}
                schemaOptions={schemaOptions}
            />
        )).toThrow(/without a scheme/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Do not build URL from defaults if value is already given', () => {
    const changeSpy = jest.fn();

    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'https://'},
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value="http://www.sulu.io"
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Build URL from defaults to pass as value to URL component', () => {
    const changeSpy = jest.fn();

    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
        defaults: {
            name: 'defaults',
            value: [
                {name: 'scheme', value: 'https://'},
                {name: 'specific_part', value: 'sulu.io'},
            ],
        },
    };

    render(
        <Url
            {...createProps()}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith('https://sulu.io', {'isDefaultValue': true});
});

test('Should not pass any arguments to onFinish callback', async() => {
    const user = userEvent.setup();
    const schemaOptions = {
        schemes: {
            name: 'schemes',
            value: [
                {name: 'http://'},
                {name: 'https://'},
            ],
        },
    };

    const finishSpy = jest.fn();

    render(
        <Url
            {...createProps()}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    await user.click(screen.getByRole('textbox'));
    await user.tab();

    expect(finishSpy).toBeCalledWith();
});
