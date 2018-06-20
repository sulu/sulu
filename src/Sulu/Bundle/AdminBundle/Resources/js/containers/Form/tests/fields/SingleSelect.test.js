// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleSelect from '../../fields/SingleSelect';

test('Pass props correctly to SingleSelect', () => {
    const schemaOptions = {
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    const singleSelect = shallow(
        <SingleSelect
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            value="test"
        />
    );

    expect(singleSelect.prop('value')).toBe('test');
    expect(singleSelect.find('Option').at(0).props()).toEqual(expect.objectContaining({
        value: 'mr',
        children: 'Mister',
    }));
    expect(singleSelect.find('Option').at(1).props()).toEqual(expect.objectContaining({
        value: 'ms',
        children: 'Miss',
    }));
});

test('Should throw an exception if defaultValue is of wrong type', () => {
    const schemaOptions = {
        default_value: {
            value: [],
        },
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    expect(() => shallow(
        <SingleSelect
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            value="test"
        />
    )).toThrow(/"default_value"/);
});

test('Should throw an exception if value is of wrong type', () => {
    const schemaOptions = {
        values: {
            value: [
                {
                    value: [],
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    expect(() => shallow(
        <SingleSelect
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            value="test"
        />
    )).toThrow(/"values"/);
});

test('Should call onFinish callback on every onChange', () => {
    const finishSpy = jest.fn();
    const schemaOptions = {
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };

    const singleSelect = shallow(
        <SingleSelect
            onChange={jest.fn()}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            schemaPath=""
            value="test"
        />
    );

    singleSelect.simulate('change');

    expect(finishSpy).toBeCalledWith();
});

test('Set default value if no value is passed', () => {
    const changeSpy = jest.fn();
    const schemaOptions = {
        default_value: {
            value: 'mr',
        },
        values: {
            value: [
                {
                    value: 'mr',
                    title: 'Mister',
                },
                {
                    value: 'ms',
                    title: 'Miss',
                },
            ],
        },
    };
    shallow(
        <SingleSelect
            onChange={changeSpy}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            value={undefined}
        />
    );

    expect(changeSpy).toBeCalledWith('mr');
});

test('Throw error if no schemaOptions are passed', () => {
    expect(() => shallow(<SingleSelect onChange={jest.fn()} onFinish={jest.fn()} schemaPath="" value={undefined} />))
        .toThrow(/"values"/);
});

test('Throw error if no value option is passed', () => {
    expect(() => shallow(<SingleSelect onChange={jest.fn()} onFinish={jest.fn()} schemaPath="" value={undefined} />))
        .toThrow(/"values"/);
});
