// @flow
import React from 'react';
import {render} from '@testing-library/react';
import moment from 'moment-timezone';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import DatePicker from '../../fields/DatePicker';
import DatePickerComponent from '../../../../components/DatePicker';
import getLatestMockProps from '../../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../../components/DatePicker', () => jest.fn(() => null));

const createProps = (props: Object = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: ({}: any),
    ...props,
});

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.valid).toBe(false);
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.options).toEqual({});
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.options).toEqual({dateFormat: false, timeFormat: true});
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.options).toEqual({timeFormat: true});
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.value).toBe(undefined);
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.disabled).toBe(true);
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    expect(datePickerProps.value).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted date value', () => {
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    datePickerProps.onChange(new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15');
});

test('Should call onFinish callback on every onChange with correctly converted time value', () => {
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    datePickerProps.onChange(new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('02:00:00');
});

test('Should call onFinish callback on every onChange with correctly converted date time value', () => {
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

    const datePickerProps: any = getLatestMockProps((DatePickerComponent: any));
    datePickerProps.onChange(new Date(Date.UTC(2018, 4, 15, 6, 30, 0)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15T08:30:00');
});
