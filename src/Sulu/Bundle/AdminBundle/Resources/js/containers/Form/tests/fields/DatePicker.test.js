// @flow
import React from 'react';
import {shallow} from 'enzyme';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import FormStore from '../../stores/FormStore';
import DatePicker from '../../fields/DatePicker';
import DatePickerComponent from '../../../../components/DatePicker';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/FormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const error = {};

    const datePicker = shallow(
        <DatePicker
            dataPath=""
            error={error}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'xyz'}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const datePicker = shallow(
        <DatePicker
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('valid')).toBe(true);
    expect(datePicker.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Pass invalid value correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const datePicker = shallow(
        <DatePicker
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'test'}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('value')).toBe(undefined);
});

test('Convert value and pass it correctly to component', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const datePicker = shallow(
        <DatePicker
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'2018-12-03'}
        />
    );

    expect(datePicker.find(DatePickerComponent).prop('value')).toBeInstanceOf(Date);
});

test('Should call onFinish callback on every onChange with correctly converted value', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const finishSpy = jest.fn();
    const changeSpy = jest.fn();

    const datePicker = shallow(
        <DatePicker
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            label="Test"
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={'2018-12-03'}
        />
    );

    datePicker.find(DatePickerComponent).simulate('change', new Date(Date.UTC(2018, 4, 15)));

    expect(finishSpy).toBeCalled();
    expect(changeSpy).toBeCalledWith('2018-05-15');
});
