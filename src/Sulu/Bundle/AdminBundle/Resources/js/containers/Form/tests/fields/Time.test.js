// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import Time from '../../fields/Time';
import DatePickerComponent from '../../../../components/DatePicker';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to Input component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {};

    const time = shallow(
        <Time
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(time.find(DatePickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const time = shallow(
        <Time
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(time.find(DatePickerComponent).prop('valid')).toBe(true);
    expect(time.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Pass invalid value correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const time = shallow(
        <Time
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value="test"
        />
    );

    expect(time.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Convert value and pass it correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const time = shallow(
        <Time
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value="'14:20:00'"
        />
    );

    // should be of type date
    expect(time.find(DatePickerComponent).prop('value')).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted value', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const time = shallow(
        <Time
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    time.find(DatePickerComponent).simulate('change', new Date(2018, 3, 15, 6, 32, 20));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('06:32:20');
});
