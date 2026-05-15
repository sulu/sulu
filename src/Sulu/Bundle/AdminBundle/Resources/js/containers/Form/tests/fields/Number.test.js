// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import fieldTypeDefaultProps from '../../../../utils/TestHelper/fieldTypeDefaultProps';
import Number from '../../fields/Number';
import NumberComponent from '../../../../components/Number';

jest.mock('../../../../components/Number', () => jest.fn(() => null));

function getLatestNumberProps() {
    const calls = (NumberComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass error correctly to component', () => {
    const formInspector = ({locale: undefined}: any);
    const error = {keyword: 'minLength', parameters: {}};

    render(
        <Number
            {...fieldTypeDefaultProps}
            error={error}
            formInspector={formInspector}
        />
    );

    expect(getLatestNumberProps().valid).toBe(false);
});

test('Pass props correctly to component', () => {
    const formInspector = ({locale: undefined}: any);

    render(
        <Number
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
        />
    );
    const numberProps = getLatestNumberProps();

    expect(numberProps.valid).toBe(true);
    expect(numberProps.disabled).toBe(true);
});

test('Pass props correctly to component inclusive schemaOptions', () => {
    const formInspector = ({locale: undefined}: any);
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
    const numberProps = getLatestNumberProps();

    expect(numberProps.valid).toBe(true);
    expect(numberProps.min).toBe(50);
    expect(numberProps.max).toBe(100);
    expect(numberProps.step).toBe(10);
});

test('Should not pass any arguments to onFinish callback', () => {
    const formInspector = ({locale: undefined}: any);
    const finishSpy = jest.fn();

    render(
        <Number
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onFinish={finishSpy}
        />
    );

    act(() => {
        getLatestNumberProps().onBlur('Test');
    });

    expect(finishSpy).toBeCalledWith();
});
