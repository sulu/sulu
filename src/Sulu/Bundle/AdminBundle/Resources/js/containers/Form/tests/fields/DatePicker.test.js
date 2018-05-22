// @flow
import React from 'react';
import {shallow} from 'enzyme';
import DatePicker from '../../fields/DatePicker';
import DatePickerComponent from '../../../../components/DatePicker';

test('Pass error correctly to component', () => {
    const error = {};

    const datePicker = shallow(
        <DatePicker
            error={error}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const datePicker = shallow(
        <DatePicker
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={undefined}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('valid')).toBe(true);
    expect(datePicker.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Pass invalid value correctly to component', () => {
    const datePicker = shallow(
        <DatePicker
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'test'}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Convert value and pass it correctly to component', () => {
    const datePicker = shallow(
        <DatePicker
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'2018-12-03'}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('value')).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted value', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const datePicker = shallow(
        <DatePicker
            onChange={changeSpy}
            onFinish={finishSpy}
            value={'2018-12-03'}
        />
    );

    datePicker.find(DatePickerComponent).simulate('change', new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15');
});
