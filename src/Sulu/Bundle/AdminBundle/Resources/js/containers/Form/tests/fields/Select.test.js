// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Select from '../../fields/Select';

let mockMultiSelectProps: Object = {};
let mockOptionProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/MultiSelect', () => {
    const MultiSelectMock: any = jest.fn((props) => {
        mockMultiSelectProps = props;

        return mockReact.createElement('div', null, props.children);
    });

    MultiSelectMock.Option = jest.fn((props) => {
        mockOptionProps.push(props);

        return mockReact.createElement('span', {'data-value': props.value}, props.children);
    });

    return MultiSelectMock;
});

beforeEach(() => {
    mockMultiSelectProps = {};
    mockOptionProps = [];
});

test('Pass props correctly to Select', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={['test']}
        />
    );

    expect(mockMultiSelectProps.values).toEqual(['test']);
    expect(mockMultiSelectProps.disabled).toBe(true);
    expect(mockOptionProps[0]).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(mockOptionProps[1]).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        default_values: {
            name: 'default_values',
            value: {},
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    expect(() => new Select(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions: (schemaOptions: any),
    }: any))).toThrow(/"default_values"/);
});

test('Should throw an exception if value is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: [],
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    const select = new Select(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions: (schemaOptions: any),
    }: any));

    expect(() => select.render()).toThrow(/"values"/);
});

test('Should call onChange with undefined if value is changed to an empty array', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    mockMultiSelectProps.onChange([]);

    expect(changeSpy).toHaveBeenCalledWith(undefined);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should call onChange with allowed values only if value contains old values', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    mockMultiSelectProps.onChange(['mr', 'removed-value']);

    expect(changeSpy).toHaveBeenCalledWith(['mr']);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Should call onFinish callback on every onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    mockMultiSelectProps.onChange([]);

    expect(finishSpy).toHaveBeenCalledWith();
});

test('Set default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_values: {
            name: 'default_values',
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={(schemaOptions: any)}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_values: {
            name: 'default_values',
            value: [{name: 'mr'}],
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith(['mr'], {'isDefaultValue': true});
});

test('Set default value to a number of 0 should work', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_values: {
            name: 'default_values',
            value: [{name: 0}],
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 0,
                    title: 'Mister',
                },
                {
                    name: 1,
                    title: 'Miss',
                },
            ],
        },
    };

    render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith([0], {'isDefaultValue': true});
});

test('Throw error if no value option is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const select = new Select(({
        ...fieldTypeDefaultProps,
        formInspector,
    }: any));

    expect(() => select.render()).toThrow(/"values"/);
});

test('Throw error if value option with wrong is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const select = new Select(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions: {values: {name: 'values', value: true}},
    }: any));

    expect(() => select.render()).toThrow(/"values"/);
});
