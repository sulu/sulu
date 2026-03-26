// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import SingleSelectComponent from '../../../../components/SingleSelect';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import SingleSelect from '../../fields/SingleSelect';

jest.mock('../../../../components/SingleSelect', () => {
    const React = require('react');
    const SingleSelect: any = jest.fn(function SingleSelect(props) {
        return <div>{props.children}</div>;
    });

    SingleSelect.Option = jest.fn(() => null);

    return SingleSelect;
});

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
}

function renderSingleSelect(props: Object = {}) {
    return render(
        <SingleSelect
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

function getLatestSingleSelectProps() {
    const calls = ((SingleSelectComponent: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getOptionProps(index: number) {
    const calls = (((SingleSelectComponent: any).Option: any).mock.calls: any);
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

test('Pass props correctly to SingleSelect', () => {
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

    renderSingleSelect({
        disabled: true,
        schemaOptions,
        value: 'test',
    });

    expect(getLatestSingleSelectProps().value).toBe('test');
    expect(getLatestSingleSelectProps().disabled).toBe(true);
    expect(getOptionProps(0)).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(getOptionProps(1)).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Pass value if no title is given to SingleSelect', () => {
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

    renderSingleSelect({
        schemaOptions,
    });

    expect(getOptionProps(0)).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'mr',
    }));
    expect(getOptionProps(1)).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'ms',
    }));
});

test('Pass undefined as option-value if value with empty name is given to SingleSelect', () => {
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

    renderSingleSelect({
        schemaOptions,
    });

    expect(getOptionProps(0)).toEqual(expect.objectContaining({
        value: undefined,
        children: 'No Selection',
    }));
    expect(getOptionProps(1)).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should throw an exception if defaultValue is of wrong type', () => {
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

    expectRenderToThrow(
        () => renderSingleSelect({
            schemaOptions,
        }),
        /"default_value"/
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
        () => renderSingleSelect({
            schemaOptions: (schemaOptions: any),
        }),
        /"values"/
    );
});

test('Should call onFinish callback on every onChange', () => {
    const finishSpy = jest.fn();

    renderSingleSelect({
        onFinish: finishSpy,
    });

    getLatestSingleSelectProps().onChange('ms');

    expect(finishSpy).toBeCalledWith();
});

test('Default value of null should not call onChange', () => {
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

    renderSingleSelect({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).not.toBeCalled();
});

test('Default value of empty string should not call onChange', () => {
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

    renderSingleSelect({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).not.toBeCalled();
});

test('Set default value if no value is passed', () => {
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

    renderSingleSelect({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).toBeCalledWith('mr', {'isDefaultValue': true});
});

test('Allow to pass one value for undefined', () => {
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

    renderSingleSelect({
        onChange: changeSpy,
        // $FlowFixMe
        schemaOptions,
    });

    expect(getOptionProps(0).value).toBeUndefined();
});

test('Set default value to a number of 0 should work', () => {
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

    renderSingleSelect({
        onChange: changeSpy,
        schemaOptions,
    });

    expect(changeSpy).toBeCalledWith(0, {'isDefaultValue': true});
});

test('Throw error if no values option is passed', () => {
    expectRenderToThrow(
        () => renderSingleSelect({
            schemaOptions: ({}: any),
        }),
        /"values"/
    );
});

test('Throw error if values option with wrong type is passed', () => {
    expectRenderToThrow(
        () => renderSingleSelect({
            schemaOptions: {values: {name: 'values', value: true}},
        }),
        /"values"/
    );
});
