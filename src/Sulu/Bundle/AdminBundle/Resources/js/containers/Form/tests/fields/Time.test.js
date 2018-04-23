// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Time from '../../fields/Time';
import DatePickerComponent from '../../../../components/DatePicker';

test('Pass error correctly to Input component', () => {
    const error = {};

    const time = shallow(
        <Time
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'xyz'}
            error={error}
        />
    );

    expect(time.find(DatePickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const time = shallow(
        <Time
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={undefined}
        />
    );

    expect(time.find(DatePickerComponent).prop('valid')).toBe(true);
    expect(time.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Pass invalid value correctly to component', () => {
    const time = shallow(
        <Time
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'test'}
        />
    );

    expect(time.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Convert value and pass it correctly to component', () => {
    const time = shallow(
        <Time
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={'14:20:00'}
        />
    );

    // should be of type date
    expect(time.find(DatePickerComponent).prop('value')).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted value', () => {
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const time = shallow(
        <Time
            onChange={changeSpy}
            onFinish={finishSpy}
            value={'14:20:00'}
        />
    );

    time.find(DatePickerComponent).simulate('change', new Date(Date.UTC(2018, 3, 15, 6, 32, 20)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('06:32:20');
});
