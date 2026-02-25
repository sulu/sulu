// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import moment from 'moment-timezone';
import userEvent from '@testing-library/user-event';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import DatePicker from '../../fields/DatePicker';

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

beforeEach(() => {
    jest.spyOn(Date, 'now').mockReturnValue(new Date(Date.UTC(2017, 3, 15, 6, 32, 20)).valueOf());
    jest.spyOn(console, 'warn').mockImplementation(() => {});
    moment.tz.setDefault('Europe/Vienna');
});

afterEach(() => {
    jest.restoreAllMocks();
});

test('Pass error correctly to component', () => {
    const error = {};
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...createProps()}
            error={error}
            fieldTypeOptions={fieldTypeOptions}
        />
    );

    const input = screen.getByRole('textbox');
    expect(input.parentElement).toHaveClass('error');
});

test('Pass options for date picker to component', () => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
        />
    );

    expect(screen.getByLabelText('su-calendar')).toBeInTheDocument();
});

test('Pass options for time picker to component', () => {
    const fieldTypeOptions = {
        dateFormat: false,
        timeFormat: true,
    };

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
        />
    );

    expect(screen.getByLabelText('su-clock')).toBeInTheDocument();
});

test('Pass options for date time picker to component', () => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: true,
    };

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
        />
    );

    expect(screen.getByLabelText('su-calendar')).toBeInTheDocument();
    expect(screen.getByPlaceholderText(/.+ .+/)).toBeInTheDocument();
});

test('Pass invalid value correctly to component', () => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
            value="test"
        />
    );

    expect(screen.getByRole('textbox')).toHaveValue('');
});

test('Pass disabled correctly to component', () => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...createProps()}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            value="test"
        />
    );

    expect(screen.getByRole('textbox')).toBeDisabled();
});

test('Convert value and pass it correctly to component', () => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
            value="2018-12-03"
        />
    );

    expect(screen.getByRole('textbox')).toHaveValue('12/03/2018');
});

test('Should call onFinish callback on every onChange with correctly converted date value', async() => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const input = screen.getByRole('textbox');
    await userEvent.type(input, '05/15/2018');
    await userEvent.tab();

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15');
});

test('Should call onFinish callback on every onChange with correctly converted time value', async() => {
    const fieldTypeOptions = {
        dateFormat: false,
        timeFormat: true,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const input = screen.getByRole('textbox');
    await userEvent.type(input, '2:00 AM');
    await userEvent.tab();

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('02:00:00');
});

test('Should call onFinish callback on every onChange with correctly converted date time value', async() => {
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: true,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <DatePicker
            {...createProps()}
            fieldTypeOptions={fieldTypeOptions}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const input = screen.getByRole('textbox');
    await userEvent.type(input, '05/15/2018 8:30 AM');
    await userEvent.tab();

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15T08:30:00');
});
