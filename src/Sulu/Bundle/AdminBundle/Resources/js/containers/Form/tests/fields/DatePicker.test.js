// @flow
import React from 'react';
import {render} from '@testing-library/react';
import moment from 'moment-timezone';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import DatePicker from '../../fields/DatePicker';
import DatePickerComponent from '../../../../components/DatePicker';

jest.mock('../../../../components/DatePicker', () => jest.fn(() => null));

function getLatestDatePickerProps() {
    const calls = (DatePickerComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

function createFormInspector() {
    return ({}: any);
}

beforeEach(() => {
    jest.clearAllMocks();
    moment.tz.setDefault('Europe/Vienna');
});

test('Pass error correctly to component', () => {
    const formInspector = createFormInspector();
    const error = {};
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            error={error}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(getLatestDatePickerProps().valid).toBe(false);
});

test('Pass options for date picker to component', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(getLatestDatePickerProps().options).toEqual({});
});

test('Pass options for time picker to component', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: false,
        timeFormat: true,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(getLatestDatePickerProps().options).toEqual({dateFormat: false, timeFormat: true});
});

test('Pass options for date time picker to component', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: true,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(getLatestDatePickerProps().options).toEqual({timeFormat: true});
});

test('Pass invalid value correctly to component', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="test"
        />
    );

    expect(getLatestDatePickerProps().value).toBe(undefined);
});

test('Pass disabled correctly to component', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="test"
        />
    );

    expect(getLatestDatePickerProps().disabled).toBe(true);
});

test('Convert value and pass it correctly to component', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="2018-12-03"
        />
    );

    expect(getLatestDatePickerProps().value).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted date value', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestDatePickerProps().onChange(new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15');
});

test('Should call onFinish callback on every onChange with correctly converted time value', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: false,
        timeFormat: true,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestDatePickerProps().onChange(new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('02:00:00');
});

test('Should call onFinish callback on every onChange with correctly converted date time value', () => {
    const formInspector = createFormInspector();
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: true,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    render(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestDatePickerProps().onChange(new Date(Date.UTC(2018, 4, 15, 6, 30, 0)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15T08:30:00');
});
