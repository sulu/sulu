// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import TargetGroupRules from '../TargetGroupRules';
import ruleRegistry from '../registries/RuleRegistry';
import ruleTypeRegistry from '../registries/RuleTypeRegistry';
import KeyValue from '../ruleTypes/KeyValue';
import SingleSelect from '../ruleTypes/SingleSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../registries/RuleTypeRegistry', () => ({
    get: jest.fn(),
}));

jest.mock('../registries/RuleRegistry', () => ({
    getAll: jest.fn(),
    get: jest.fn(),
}));

test('Render an empty list of rules', () => {
    expect(render(<TargetGroupRules onChange={jest.fn()} value={[]} />)).toMatchSnapshot();
});

test('Render a list of rules', () => {
    ruleRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'browser':
                return {
                    name: 'Browser',
                    type: {
                        name: 'select',
                        options: {
                            name: 'browser',
                            options: [
                                {id: 'firefox', name: 'Firefox'},
                                {id: 'chrome', name: 'Chrome'},
                            ],
                        },
                    },
                };
            case 'query_string':
                return {
                    name: 'Query String',
                    type: {
                        name: 'key_value',
                        options: {
                            keyName: 'parameter',
                            valueName: 'value',
                        },
                    },
                };
        }
    });

    const value = [
        {
            conditions: [
                {
                    condition: {browser: 'Opera'},
                    type: 'browser',
                },
            ],
            frequency: 1,
            title: 'Rule 1',
        },
        {
            conditions: [
                {
                    condition: {browser: 'Opera'},
                    type: 'browser',
                },
                {
                    condition: {parameter: 'test', value: 'value'},
                    type: 'query_string',
                },
            ],
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
    ruleRegistry.getAll.mockReturnValue({
        browser: {
            name: 'Browser',
            type: {
                name: 'select',
                options: {
                    name: 'browser',
                    options: [
                        {id: 'firefox', name: 'Firefox'},
                        {id: 'chrome', name: 'Chrome'},
                    ],
                },
            },
        },
        query_string: {
            name: 'Query String',
            type: {
                name: 'key_value',
                options: {
                    keyName: 'parameter',
                    valueName: 'value',
                },
            },
        },
    });

    ruleRegistry.get.mockImplementation((key) => ruleRegistry.getAll()[key]);

    ruleTypeRegistry.get.mockImplementation((type) => {
        switch (type) {
            case 'select':
                return SingleSelect;
            case 'key_value':
                return KeyValue;
        }
    });

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

    targetGroupRules.find('ConditionList Button[icon="su-plus"]').prop('onClick')();
    targetGroupRules.find('ConditionList Button[icon="su-plus"]').prop('onClick')();
    targetGroupRules.update();

    targetGroupRules.find('ConditionList Condition').at(0).find('SingleSelect DisplayValue').prop('onClick')();
    targetGroupRules.update();
    targetGroupRules.find('ConditionList Condition').at(0).find('SingleSelect Option button').at(0).prop('onClick')();
    targetGroupRules.update();
    targetGroupRules.find('ConditionList Condition').at(0).find('SingleSelect DisplayValue').at(1).prop('onClick')();
    targetGroupRules.update();
    targetGroupRules.find('ConditionList Condition').at(0).find('SingleSelect Option button').at(0).prop('onClick')();

    targetGroupRules.find('ConditionList Condition').at(1).find('SingleSelect DisplayValue').prop('onClick')();
    targetGroupRules.update();
    targetGroupRules.find('ConditionList Condition').at(1).find('SingleSelect Option button').at(1).prop('onClick')();
    targetGroupRules.update();
    targetGroupRules.find('ConditionList Condition').at(1).find('KeyValue Input').at(0).prop('onChange')('parameter');
    targetGroupRules.find('ConditionList Condition').at(1).find('KeyValue Input').at(1).prop('onChange')('value');

    targetGroupRules.find('RuleOverlay Button[skin="primary"]').prop('onClick')();

    expect(changeSpy).toBeCalledWith([
        {
            conditions: [
                {
                    condition: {
                        browser: 'firefox',
                    },
                    type: 'browser',
                },
                {
                    condition: {
                        parameter: 'parameter',
                        value: 'value',
                    },
                    type: 'query_string',
                },
            ],
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
