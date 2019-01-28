// @flow
import React from 'react';
import {shallow} from 'enzyme';
import moment from 'moment-timezone';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import DatePicker from '../../fields/DatePicker';
import DatePickerComponent from '../../../../components/DatePicker';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

beforeEach(() => {
    moment.tz.setDefault('Europe/Vienna');
});

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {};
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            error={error}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('valid')).toBe(false);
});

test('Pass options for date picker to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('options')).toEqual({});
});

test('Pass options for time picker to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: false,
        timeFormat: true,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('options')).toEqual({dateFormat: false, timeFormat: true});
});

test('Pass options for date time picker to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: true,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('options')).toEqual({timeFormat: true});
});

test('Pass invalid value correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="test"
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Pass disabled correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            disabled={true}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="test"
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('disabled')).toBe(true);
});

test('Convert value and pass it correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            value="2018-12-03"
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('value')).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted date value', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: false,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    datePicker.find(DatePickerComponent).simulate('change', new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15');
});

test('Should call onFinish callback on every onChange with correctly converted time value', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: false,
        timeFormat: true,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    datePicker.find(DatePickerComponent).simulate('change', new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('02:00:00');
});

test('Should call onFinish callback on every onChange with correctly converted date time value', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const fieldTypeOptions = {
        dateFormat: true,
        timeFormat: true,
    };

    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const datePicker = shallow(
        <DatePicker
            {...fieldTypeDefaultProps}
            fieldTypeOptions={fieldTypeOptions}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    datePicker.find(DatePickerComponent).simulate('change', new Date(Date.UTC(2018, 4, 15, 6, 30, 0)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15T08:30:00');
});
