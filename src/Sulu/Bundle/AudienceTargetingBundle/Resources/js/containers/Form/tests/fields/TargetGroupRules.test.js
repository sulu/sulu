// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import TargetGroupRule from '../../fields/TargetGroupRules';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../TargetGroupRules/RuleOverlay', () => jest.fn(() => null));

const ruleOverlayComponent = ((jest.requireMock('../../../TargetGroupRules/RuleOverlay'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

test('Pass a default value of an empty array to the component', () => {
    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={({}: any)}
        />
    );

    expect(screen.queryByText('Rule 1')).not.toBeInTheDocument();
});

test('Pass the given value to the component', () => {
    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={({}: any)}
            value={value}
        />
    );

    expect(screen.getByText('Rule 1')).toBeInTheDocument();
});

test('Call onChange and onFinish if value of component changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={({}: any)}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    getLatestMockProps(ruleOverlayComponent).onConfirm({
        conditions: [],
        frequency: 1,
        title: 'Rule 1',
    });

    expect(changeSpy).toBeCalledWith([{
        conditions: [],
        frequency: 1,
        title: 'Rule 1',
    }]);
    expect(finishSpy).toBeCalledWith();
});
