// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Select from '../../fields/Select';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass props correctly to Select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    render(
        <Select
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={['test']}
        />
    );

    expect(screen.getByRole('button')).toBeDisabled();
    expect(screen.getByText('sulu_admin.none_selected')).toBeInTheDocument();
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        default_values: {
            name: 'default_values',
            value: {},
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <Select
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={(schemaOptions: any)}
            />
        )).toThrow(/"default_values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Should throw an exception if value is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: [],
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <Select
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={(schemaOptions: any)}
            />
        )).toThrow(/"values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Should call onChange with undefined if value is changed to an empty array', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={['mr']}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: /Mister$/}));

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});

test('Should call onChange with allowed values only if value contains old values', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            value={['removed-value']}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'Mister'}));

    expect(changeSpy).toBeCalledWith(['mr']);
    expect(finishSpy).toBeCalledWith();
});

test('Should call onFinish callback on every onChange', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: 'Mister'}));

    expect(finishSpy).toBeCalledWith();
});

test('Set default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_values: {
            name: 'default_values',
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={(schemaOptions: any)}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_values: {
            name: 'default_values',
            value: [{name: 'mr'}],
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith(['mr'], {'isDefaultValue': true});
});

test('Set default value to a number of 0 should work', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_values: {
            name: 'default_values',
            value: [{name: 0}],
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 0,
                    title: 'Mister',
                },
                {
                    name: 1,
                    title: 'Miss',
                },
            ],
        },
    };
    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith([0], {'isDefaultValue': true});
});

test('Throw error if no value option is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <Select
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
            />)
        ).toThrow(/"values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Throw error if value option with wrong is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <Select
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={{values: {name: 'values', value: true}}}
            />)
        ).toThrow(/"values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});
