// @flow
import React from 'react';
import {render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import ResourceStore from '../../../../stores/ResourceStore';
import FormInspector from '../../FormInspector';
import ResourceFormStore from '../../stores/ResourceFormStore';
import Number from '../../fields/Number';

let mockNumberProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../stores/ResourceStore', () => jest.fn());
jest.mock('../../stores/ResourceFormStore', () => jest.fn());
jest.mock('../../FormInspector', () => jest.fn());
jest.mock('../../../../components/Number', () => jest.fn((props) => {
    mockNumberProps = props;

    return mockReact.createElement('input', {type: 'number'});
}));

beforeEach(() => {
    mockNumberProps = {};
});

test('Pass error correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Number
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(mockNumberProps.valid).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    render(
        <Number
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );

    expect(mockNumberProps.valid).toBe(true);
    expect(mockNumberProps.disabled).toBe(true);
});

test('Pass props correctly to component inclusive schemaOptions', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const schemaOptions = {
        min: {
            name: 'min',
            value: 50,
        },
        max: {
            name: 'max',
            value: 100,
        },
        step: {
            name: 'step',
            value: 10,
        },
    };

    render(
        <Number
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(mockNumberProps.valid).toBe(true);
    expect(mockNumberProps.min).toBe(50);
    expect(mockNumberProps.max).toBe(100);
    expect(mockNumberProps.step).toBe(10);
});

test('Should not pass any arguments to onFinish callback', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'snippets'));
    const finishSpy = jest.fn();

    render(
        <Number
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    mockNumberProps.onBlur('Test');

    expect(finishSpy).toHaveBeenCalledWith();
});
