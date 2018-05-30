// @flow
import React from 'react';
import {shallow} from 'enzyme';
import Number from '../../fields/Number';
import NumberComponent from '../../../../components/Number';

test('Pass error correctly to component', () => {
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <Number
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={50.2}
            error={error}
        />
    );

    expect(field.find(NumberComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const field = shallow(
        <Number
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={50.2}
        />
    );

    expect(field.find(NumberComponent).prop('valid')).toBe(true);
});

test('Pass props correctly to component inclusive schemaOptions', () => {
    const schemaOptions = {
        min: {
            value: 50,
        },
        max: {
            value: 100,
        },
        step: {
            value: 10,
        },
    };

    const field = shallow(
        <Number
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={50.2}
            schemaOptions={schemaOptions}
        />
    );

    expect(field.find(NumberComponent).prop('valid')).toBe(true);
    expect(field.find(NumberComponent).prop('min')).toBe(50);
    expect(field.find(NumberComponent).prop('max')).toBe(100);
    expect(field.find(NumberComponent).prop('step')).toBe(10);
});
