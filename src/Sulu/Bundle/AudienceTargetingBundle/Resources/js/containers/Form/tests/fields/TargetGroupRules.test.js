// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import TargetGroupRule from '../../fields/TargetGroupRules';
import TargetGroupRulesComponent from '../../../../components/TargetGroupRules';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn());

test('Pass a default value of an empty array to the component', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const targetGroupRules = shallow(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    );

    expect(targetGroupRules.find(TargetGroupRulesComponent).prop('value')).toEqual([]);
});

test('Pass the given value to the component', () => {
    const value = [];
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const targetGroupRules = shallow(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            value={value}
        />
    );

    expect(targetGroupRules.find(TargetGroupRulesComponent).prop('value')).toBe(value);
});

test('Call onChange and onFinish if value of componetn changes', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const targetGroupRules = shallow(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    targetGroupRules.find(TargetGroupRulesComponent).prop('onChange')([{}]);

    expect(changeSpy).toBeCalledWith([{}]);
    expect(finishSpy).toBeCalledWith();
});
