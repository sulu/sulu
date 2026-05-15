// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import TargetGroupRule from '../../fields/TargetGroupRules';
import TargetGroupRulesComponent from '../../../../containers/TargetGroupRules';

jest.mock('../../../../containers/TargetGroupRules', () => jest.fn(() => null));

function getLatestTargetGroupRulesProps() {
    const calls = (TargetGroupRulesComponent: any).mock.calls;
    return calls[calls.length - 1][0];
}

test('Pass a default value of an empty array to the component', () => {
    const formInspector = ({}: any);

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(getLatestTargetGroupRulesProps().value).toEqual([]);
});

test('Pass the given value to the component', () => {
    const value = [];
    const formInspector = ({}: any);

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(getLatestTargetGroupRulesProps().value).toBe(value);
});

test('Call onChange and onFinish if value of componetn changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = ({}: any);

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    act(() => {
        getLatestTargetGroupRulesProps().onChange([{}]);
    });

    expect(changeSpy).toBeCalledWith([{}]);
    expect(finishSpy).toBeCalledWith();
});
