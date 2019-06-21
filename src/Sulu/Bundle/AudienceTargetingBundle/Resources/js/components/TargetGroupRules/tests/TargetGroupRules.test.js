// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import TargetGroupRules from '../TargetGroupRules';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render an empty list of rules', () => {
    expect(render(<TargetGroupRules onChange={jest.fn()} value={[]} />)).toMatchSnapshot();
});

test('Render a list of rules', () => {
    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
        {
            conditions: [],
            frequency: 3,
            title: 'Rule 3',
        },
    ];

    expect(render(<TargetGroupRules onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Add a new rule', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    const targetGroupRules = mount(<TargetGroupRules onChange={changeSpy} value={value} />);

    targetGroupRules.find('Button[icon="su-plus"]').prop('onClick')();

    targetGroupRules.update();

    targetGroupRules.find('RuleOverlay Input').prop('onChange')('Rule 2');
    targetGroupRules.find('RuleOverlay SingleSelect').prop('onChange')(2);

    targetGroupRules.find('RuleOverlay Button[skin="primary"]').prop('onClick')();

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
    ]);
});

test('Edit an existing rule', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
    ];

    const targetGroupRules = mount(<TargetGroupRules onChange={changeSpy} value={value} />);

    targetGroupRules.find('ButtonCell[rowIndex=0] button').prop('onClick')();
    targetGroupRules.update();

    expect((targetGroupRules.find('RuleOverlay Input').prop('value'))).toEqual('Rule 1');
    expect((targetGroupRules.find('RuleOverlay SingleSelect').prop('value'))).toEqual(1);

    targetGroupRules.find('RuleOverlay Input').prop('onChange')('Rule 1 edited');
    targetGroupRules.find('RuleOverlay SingleSelect').prop('onChange')(3);

    targetGroupRules.find('RuleOverlay Button[skin="primary"]').prop('onClick')();

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [],
            frequency: 3,
            title: 'Rule 1 edited',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
    ]);
});

test('Close without adding a new rule', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ];

    const targetGroupRules = mount(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(targetGroupRules.find('RuleOverlay').prop('open')).toEqual(false);
    targetGroupRules.find('Button[icon="su-plus"]').prop('onClick')();

    targetGroupRules.update();
    expect(targetGroupRules.find('RuleOverlay').prop('open')).toEqual(true);

    targetGroupRules.find('RuleOverlay span.su-times').simulate('click');

    expect(targetGroupRules.find('RuleOverlay').prop('open')).toEqual(false);
    expect(changeSpy).not.toBeCalled();
});

test('Remove rules', () => {
    const changeSpy = jest.fn();

    const value = [
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [],
            frequency: 2,
            title: 'Rule 2',
        },
        {
            conditions: [],
            frequency: 3,
            title: 'Rule 3',
        },
    ];

    const targetGroupRules = mount(<TargetGroupRules onChange={changeSpy} value={value} />);

    expect(targetGroupRules.find('Button[icon="su-trash-alt"]').prop('disabled')).toEqual(true);

    targetGroupRules.find('Row[rowIndex=1] input[type="checkbox"]').getDOMNode().checked = true;
    targetGroupRules.find('Row[rowIndex=2] input[type="checkbox"]').getDOMNode().checked = true;
    targetGroupRules.find('Row[rowIndex=1] input[type="checkbox"]')
        .simulate('change', {currentTarget: {checked: true}});
    targetGroupRules.find('Row[rowIndex=2] input[type="checkbox"]')
        .simulate('change', {currentTarget: {checked: true}});

    targetGroupRules.update();
    expect(targetGroupRules.find('Button[icon="su-trash-alt"]').prop('disabled')).toEqual(false);

    targetGroupRules.find('Button[icon="su-trash-alt"]').prop('onClick')();

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [],
            frequency: 1,
            title: 'Rule 1',
        },
    ]);
});
