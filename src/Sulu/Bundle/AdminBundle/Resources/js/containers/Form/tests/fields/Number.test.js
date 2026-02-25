// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Number from '../../fields/Number';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Number
            {...createProps()}
            error={error}
        />
    );
    const input = screen.getByRole('spinbutton');

    expect(input.parentElement).toHaveClass('error');
});

test('Pass props correctly to component', () => {
    render(
        <Number
            {...createProps()}
            disabled={true}
        />
    );
    const input = screen.getByRole('spinbutton');

    expect(input).toBeDisabled();
    expect(input.parentElement).not.toHaveClass('error');
});

test('Pass props correctly to component inclusive schemaOptions', () => {
    const schemaOptions = {
        min: {
            name: 'min',
            value: 50,
        },
        max: {
            name: 'max',
            value: 100,
        },
        step: {
            name: 'step',
            value: 10,
        },
    };

    render(
        <Number
            {...createProps()}
            schemaOptions={schemaOptions}
        />
    );
    const input = screen.getByRole('spinbutton');

    expect(input).toHaveAttribute('min', '50');
    expect(input).toHaveAttribute('max', '100');
    expect(input).toHaveAttribute('step', '10');
    expect(input.parentElement).not.toHaveClass('error');
});

test('Should not pass any arguments to onFinish callback', async() => {
    const user = userEvent.setup();
    const finishSpy = jest.fn();

    render(
        <Number
            {...createProps()}
            onFinish={finishSpy}
        />
    );

    const input = screen.getByRole('spinbutton');
    await user.click(input);
    await user.tab();

    expect(finishSpy).toBeCalledWith();
});
