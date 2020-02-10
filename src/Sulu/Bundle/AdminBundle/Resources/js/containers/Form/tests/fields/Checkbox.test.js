// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Checkbox from '../../fields/Checkbox';
import CheckboxComponent from '../../../../components/Checkbox';
import Toggler from '../../../../components/Toggler';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass the label correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{label: {name: 'label', title: 'Checkbox Title'}}}
        />
    );
    expect(checkbox.find(CheckboxComponent).prop('children')).toEqual('Checkbox Title');
});

test('Pass disabled correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{label: {name: 'label', title: 'Checkbox Title'}}}
        />
    );
    expect(checkbox.find(CheckboxComponent).props().disabled).toEqual(true);
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 'not-boolean',
        },
    };

    expect(() => shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    )).toThrow(/"default_value"/);
});

test('Set default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();

    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: null,
        },
    };

    shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();

    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: false,
        },
    };

    shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toBeCalledWith(false);
});

test('Do not set default value if a value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();

    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: false,
        },
    };

    shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
            value={false}
        />
    );

    expect(changeSpy).not.toBeCalled();
});

test('Pass the value of true correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={true}
        />
    );
    expect(checkbox.find(CheckboxComponent).prop('checked')).toEqual(true);
});

test('Pass the value of false correctly to Checkbox component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={false}
        />
    );
    expect(checkbox.find(CheckboxComponent).prop('checked')).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Checkbox', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );
    checkbox.find(CheckboxComponent).simulate('change', true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});

test('Pass the label correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const schemaOptions = {
        label: {name: 'label', title: 'Toggler Title'},
        type: {name: 'type', value: 'toggler'},
    };

    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );
    expect(checkbox.find(Toggler).prop('children')).toEqual('Toggler Title');
});

test('Pass disabled correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const schemaOptions = {
        label: {name: 'label', title: 'Toggler Title'},
        type: {name: 'type', value: 'toggler'},
    };

    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            label="Test"
            schemaOptions={schemaOptions}
        />
    );
    expect(checkbox.find(Toggler).props().disabled).toEqual(true);
});

test('Pass the value of true correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            label="Test"
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
            value={true}
        />
    );
    expect(checkbox.find(Toggler).prop('checked')).toEqual(true);
});

test('Pass the value of false correctly to Toggler component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
            value={false}
        />
    );
    expect(checkbox.find(Toggler).prop('checked')).toEqual(false);
});

test('Call onChange and onFinish on the changed callback of the Toggler', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const checkbox = shallow(
        <Checkbox
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={{type: {name: 'type', value: 'toggler'}}}
        />
    );
    checkbox.find(Toggler).simulate('change', true);

    expect(changeSpy).toBeCalledWith(true);
    expect(finishSpy).toBeCalledWith();
});
