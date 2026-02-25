// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Checkbox from '../../fields/Checkbox';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Render heading skin with icon and description', () => {
    const schemaOptions = {
        description: {
            name: 'description',
            title: 'Hides a block',
        },
        icon: {
            name: 'icon',
            value: 'su-eye',
        },
        label: {
            name: 'label',
            title: 'Hide block',
        },
        skin: {
            name: 'skin',
            value: 'heading',
        },
    };

    render(
        <Checkbox {...createProps({schemaOptions})} />
    );

    expect(screen.getByText('Hide block')).toBeInTheDocument();
    expect(screen.getByText('Hides a block')).toBeInTheDocument();
    expect(screen.getByLabelText('su-eye')).toBeInTheDocument();
});

test('Pass the label correctly to Checkbox component', () => {
    render(
        <Checkbox
            {...createProps({
                label: 'Test',
                schemaOptions: {label: {name: 'label', title: 'Checkbox Title'}},
            })}
        />
    );

    expect(screen.getByText('Checkbox Title')).toBeInTheDocument();
});

test('Pass disabled correctly to Checkbox component', () => {
    render(
        <Checkbox
            {...createProps({
                disabled: true,
                label: 'Test',
                schemaOptions: {label: {name: 'label', title: 'Checkbox Title'}},
            })}
        />
    );

    expect(screen.getByRole('checkbox')).toBeDisabled();
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 'not-boolean',
        },
    };

    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    try {
        expect(() => render(<Checkbox {...createProps({schemaOptions})} />)).toThrow(/"default_value"/);
    } finally {
        consoleErrorSpy.mockRestore();
    }
});

test('Set default value of null should not call onChange', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: null,
        },
    };

    render(
        <Checkbox
            {...createProps({
                onChange: changeSpy,
                schemaOptions,
            })}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: false,
        },
    };

    render(
        <Checkbox
            {...createProps({
                onChange: changeSpy,
                schemaOptions,
            })}
        />
    );

    expect(changeSpy).toBeCalledWith(false, {isDefaultValue: true});
});

test('Do not set default value if a value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: false,
        },
    };

    render(
        <Checkbox
            {...createProps({
                onChange: changeSpy,
                schemaOptions,
                value: false,
            })}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Pass the value of true correctly to Checkbox component', () => {
    render(<Checkbox {...createProps({value: true})} />);

    expect(screen.getByRole('checkbox')).toBeChecked();
});

test('Pass the value of false correctly to Checkbox component', () => {
    render(<Checkbox {...createProps({value: false})} />);

    expect(screen.getByRole('checkbox')).not.toBeChecked();
});

test('Call onChange and onFinish on the changed callback of the Checkbox', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    render(
        <Checkbox
            {...createProps({
                onChange: changeSpy,
                onFinish: finishSpy,
            })}
        />
    );

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Pass the label correctly to Toggler component', () => {
    const schemaOptions = {
        label: {name: 'label', title: 'Toggler Title'},
        type: {name: 'type', value: 'toggler'},
    };
    render(
        <Checkbox
            {...createProps({
                label: 'Test',
                schemaOptions,
            })}
        />
    );

    expect(screen.getByText('Toggler Title')).toBeInTheDocument();
});

test('Pass disabled correctly to Toggler component', () => {
    const schemaOptions = {
        label: {name: 'label', title: 'Toggler Title'},
        type: {name: 'type', value: 'toggler'},
    };
    render(
        <Checkbox
            {...createProps({
                disabled: true,
                label: 'Test',
                schemaOptions,
            })}
        />
    );

    expect(screen.getByRole('checkbox')).toBeDisabled();
});

test('Pass the value of true correctly to Toggler component', () => {
    render(
        <Checkbox
            {...createProps({
                label: 'Test',
                schemaOptions: {type: {name: 'type', value: 'toggler'}},
                value: true,
            })}
        />
    );

    expect(screen.getByRole('checkbox')).toBeChecked();
});

test('Pass the value of false correctly to Toggler component', () => {
    render(
        <Checkbox
            {...createProps({
                schemaOptions: {type: {name: 'type', value: 'toggler'}},
                value: false,
            })}
        />
    );

    expect(screen.getByRole('checkbox')).not.toBeChecked();
});

test('Call onChange and onFinish on the changed callback of the Toggler', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    render(
        <Checkbox
            {...createProps({
                onChange: changeSpy,
                onFinish: finishSpy,
                schemaOptions: {type: {name: 'type', value: 'toggler'}},
            })}
        />
    );

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Call onChange and onFinish on the changed callback of the Toggler with the header skin', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    render(
        <Checkbox
            {...createProps({
                onChange: changeSpy,
                onFinish: finishSpy,
                schemaOptions: {skin: {name: 'skin', value: 'heading'}, type: {name: 'type', value: 'toggler'}},
            })}
        />
    );

    await user.click(screen.getByRole('checkbox'));

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});
