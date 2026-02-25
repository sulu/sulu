// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import TargetGroupRule from '../../fields/TargetGroupRules';
import TargetGroupRulesComponent from '../../../TargetGroupRules';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn());
jest.mock('../../../TargetGroupRules', () => jest.fn(() => null));

test('Pass a default value of an empty array to the component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    const targetGroupRulesProps: any = getLatestMockProps((TargetGroupRulesComponent: any));
    expect(targetGroupRulesProps.value).toEqual([]);
});

test('Pass the given value to the component', () => {
    const value = [];
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    const targetGroupRulesProps: any = getLatestMockProps((TargetGroupRulesComponent: any));
    expect(targetGroupRulesProps.value).toBe(value);
});

test('Call onChange and onFinish if value of component changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    const targetGroupRulesProps: any = getLatestMockProps((TargetGroupRulesComponent: any));
    targetGroupRulesProps.onChange([{
        conditions: [],
        frequency: 1,
        title: 'Rule 1',
    }]);

    expect(changeSpy).toBeCalledWith([{
        conditions: [],
        frequency: 1,
        title: 'Rule 1',
    }]);
    expect(finishSpy).toBeCalledWith();
});
