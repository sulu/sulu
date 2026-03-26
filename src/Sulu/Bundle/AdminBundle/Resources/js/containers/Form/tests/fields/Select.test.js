// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Select from '../../fields/Select';
import MultiSelectComponent from '../../../../components/MultiSelect';

jest.mock('../../../../components/MultiSelect', () => {
    const React = require('react');
    const MultiSelect: any = jest.fn(function MultiSelect(props) {
        return <div>{props.children}</div>;
    });

    MultiSelect.Option = jest.fn(() => null);

    return MultiSelect;
});

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
}

function renderSelect(props: Object = {}) {
    return render(
        <Select
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector()}
            schemaOptions={{
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
            }}
            {...props}
        />
    );
}

function getLatestMultiSelectProps() {
    const calls = ((MultiSelectComponent: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getOptionProps(index: number) {
    const calls = (((MultiSelectComponent: any).Option: any).mock.calls: any);
    return calls[index][0];
}

function expectRenderToThrow(renderFn: () => void, message: RegExp) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    expect(renderFn).toThrow(message);
    consoleErrorSpy.mockRestore();
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass props correctly to Select', () => {
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

    renderSelect({
        disabled: true,
        schemaOptions,
        value: ['test'],
    });

    expect(getLatestMultiSelectProps().values).toEqual(['test']);
    expect(getLatestMultiSelectProps().disabled).toBe(true);
    expect(getOptionProps(0)).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(getOptionProps(1)).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should throw an exception if defaultValue is of wrong type', () => {
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

    expectRenderToThrow(
        () => renderSelect({
            schemaOptions: (schemaOptions: any),
        }),
        /"default_values"/
    );
});

test('Should throw an exception if value is of wrong type', () => {
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

    expectRenderToThrow(
        () => renderSelect({
            schemaOptions: (schemaOptions: any),
        }),
        /"values"/
    );
});

test('Should call onChange with undefined if value is changed to an empty array', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderSelect({
        onChange: changeSpy,
        onFinish: finishSpy,
    });

    getLatestMultiSelectProps().onChange([]);

    expect(changeSpy).toBeCalledWith(undefined);
    expect(finishSpy).toBeCalledWith();
});

test('Should call onChange with allowed values only if value contains old values', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    renderSelect({
        onChange: changeSpy,
        onFinish: finishSpy,
    });

    getLatestMultiSelectProps().onChange(['mr', 'removed-value']);

    expect(changeSpy).toBeCalledWith(['mr']);
    expect(finishSpy).toBeCalledWith();
});

test('Should call onFinish callback on every onChange', () => {
    const finishSpy = jest.fn();

    renderSelect({
        onFinish: finishSpy,
    });

    getLatestMultiSelectProps().onChange([]);

    expect(finishSpy).toBeCalledWith();
});

test('Set default value of null should not call onChange', () => {
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

    renderSelect({
        onChange: changeSpy,
        schemaOptions: (schemaOptions: any),
    });

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
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

    renderSelect({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).toBeCalledWith(['mr'], {'isDefaultValue': true});
});

test('Set default value to a number of 0 should work', () => {
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

    renderSelect({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).toBeCalledWith([0], {'isDefaultValue': true});
});

test('Throw error if no value option is passed', () => {
    expectRenderToThrow(
        () => renderSelect({
            schemaOptions: {},
        }),
        /"values"/
    );
});

test('Throw error if value option with wrong is passed', () => {
    expectRenderToThrow(
        () => renderSelect({
            schemaOptions: {values: {name: 'values', value: true}},
        }),
        /"values"/
    );
});
