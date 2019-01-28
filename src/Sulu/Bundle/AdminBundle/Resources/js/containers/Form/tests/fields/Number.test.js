// @flow
import React from 'react';
import {shallow} from 'enzyme';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Number from '../../fields/Number';
import NumberComponent from '../../../../components/Number';

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    const field = shallow(
        <Number
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(field.find(NumberComponent).prop('valid')).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const field = shallow(
        <Number
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(field.find(NumberComponent).prop('valid')).toBe(true);
    expect(field.find(NumberComponent).prop('disabled')).toBe(true);
});

test('Pass props correctly to component inclusive schemaOptions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
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
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(field.find(NumberComponent).prop('valid')).toBe(true);
    expect(field.find(NumberComponent).prop('min')).toBe(50);
    expect(field.find(NumberComponent).prop('max')).toBe(100);
    expect(field.find(NumberComponent).prop('step')).toBe(10);
});

test('Should not pass any arguments to onFinish callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const finishSpy = jest.fn();

    const input = shallow(
        <Number
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    input.find('Number').prop('onBlur')('Test');

    expect(finishSpy).toBeCalledWith();
});
