// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelect from '../../fields/SingleSelect';

let mockSingleSelectProps: Object = {};
let mockOptionProps: Array<Object> = [];

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/SingleSelect', () => {
    const SingleSelectMock: any = jest.fn((props) => {
        mockSingleSelectProps = props;

        return mockReact.createElement('div', null, props.children);
    });

    SingleSelectMock.Option = jest.fn((props) => {
        mockOptionProps.push(props);

        return mockReact.createElement('span', {'data-value': props.value}, props.children);
    });

    return SingleSelectMock;
});

beforeEach(() => {
    mockSingleSelectProps = {};
    mockOptionProps = [];
});

test('Pass props correctly to SingleSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
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
    });

    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value="test"
        />
    );

    expect(mockSingleSelectProps.value).toBe('test');
    expect(mockSingleSelectProps.disabled).toBe(true);
    expect(mockOptionProps[0]).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(mockOptionProps[1]).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Pass value if no title is given to SingleSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                },
                {
                    name: 'ms',
                },
            ],
        },
    });

    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockOptionProps[0]).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'mr',
    }));
    expect(mockOptionProps[1]).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'ms',
    }));
});

test('Pass undefined as option-value if value with empty name is given to SingleSelect', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = observable({
        values: {
            name: 'values',
            value: [
                {
                    name: '',
                    title: 'No Selection',
                },
                {
                    name: 'ms',
                    title: 'Miss',
                },
            ],
        },
    });

    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockOptionProps[0]).toEqual(expect.objectContaining({
        value: undefined,
        children: 'No Selection',
    }));
    expect(mockOptionProps[1]).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: [],
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

    expect(() => new SingleSelect(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions,
    }: any))).toThrow(/"default_value"/);
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

    const singleSelect = new SingleSelect(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions: (schemaOptions: any),
    }: any));

    expect(() => singleSelect.render()).toThrow(/"values"/);
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    mockSingleSelectProps.onChange();

    expect(finishSpy).toHaveBeenCalledWith();
});

test('Default value of null should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: null,
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
            ],
        },
    };

    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Default value of empty string should not call onChange', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: '',
        },
        values: {
            name: 'values',
            value: [
                {
                    name: 'mr',
                    title: 'Mister',
                },
            ],
        },
    };

    render(
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).not.toHaveBeenCalled();
});

test('Set default value if no value is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 'mr',
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith('mr', {'isDefaultValue': true});
});

test('Allow to pass one value for undefined', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        values: {
            name: 'values',
            value: [
                {
                    name: undefined,
                    title: 'None selected',
                },
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={(schemaOptions: any)}
        />
    );

    expect(mockOptionProps[0].value).toBeUndefined();
});

test('Set default value to a number of 0 should work', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            name: 'default_value',
            value: 0,
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
        <SingleSelect
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={schemaOptions}
        />
    );

    expect(changeSpy).toHaveBeenCalledWith(0, {'isDefaultValue': true});
});

test('Throw error if no values option is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const singleSelect = new SingleSelect(({
        ...fieldTypeDefaultProps,
        formInspector,
    }: any));

    expect(() => singleSelect.render()).toThrow(/"values"/);
});

test('Throw error if values option with wrong type is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const singleSelect = new SingleSelect(({
        ...fieldTypeDefaultProps,
        formInspector,
        schemaOptions: {values: {name: 'values', value: true}},
    }: any));

    expect(() => singleSelect.render()).toThrow(/"values"/);
});
