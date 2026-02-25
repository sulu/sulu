// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelect from '../../fields/SingleSelect';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Pass props correctly to SingleSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
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
    });
    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="test"
        />
    );

    expect(screen.getByRole('button')).toBeDisabled();
    expect(screen.getByText('sulu_admin.please_choose')).toBeInTheDocument();
});

test('Pass value if no title is given to SingleSelect', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                },
                {
                    name: 'ms',
                },
            ],
        },
    });
    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));

    expect(screen.getByRole('button', {name: 'mr'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'ms'})).toBeInTheDocument();
});

test('Pass undefined as option-value if value with empty name is given to SingleSelect', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = observable({
        values: {
            name: 'values',
            value: [
                {
                    name: '',
                    title: 'No Selection',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    });
    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: /No Selection$/}));

    expect(changeSpy).toBeCalledWith(undefined);
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: [],
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
            <SingleSelect
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )).toThrow(/"default_value"/);
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
            <SingleSelect
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={(schemaOptions: any)}
            />
        )).toThrow(/"values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
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
        <SingleSelect
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

test('Default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: null,
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
            ],
        },
    };
    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Default value of empty string should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: '',
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
            ],
        },
    };
    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 'mr',
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith('mr', {'isDefaultValue': true});
});

test('Allow to pass one value for undefined', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: undefined,
                    title: 'None selected',
                },
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={(schemaOptions: any)}
        />
    );

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: /None selected$/}));

    expect(changeSpy).toBeCalledWith(undefined);
});

test('Set default value to a number of 0 should work', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 0,
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith(0, {'isDefaultValue': true});
});

test('Throw error if no values option is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <SingleSelect
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
            />)
        ).toThrow(/"values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Throw error if values option with wrong type is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(
            <SingleSelect
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={{values: {name: 'values', value: true}}}
            />)
        ).toThrow(/"values"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});
