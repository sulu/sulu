// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import RuleOverlay from '../RuleOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Render RuleOverlay without value', () => {
    const ruleOverlay = mount(<RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />);
    expect(ruleOverlay.render())
        .toMatchSnapshot();
});

test('Write passed values to input, single select and condition list when overlay is opened', () => {
    const conditions = [
        {
            condition: {
                parameter: 'asdf',
                value: 'jklö',
            },
            type: 'query_string',
        },
    ];

    const ruleOverlay = shallow(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    ruleOverlay.setProps({open: true});
    ruleOverlay.update();

    expect(ruleOverlay.find('Input').prop('value')).toEqual('Rule 1');
    expect(ruleOverlay.find('SingleSelect').prop('value')).toEqual(2);
    expect(ruleOverlay.find('ConditionList').prop('value')).toEqual(conditions);

    ruleOverlay.find('Input').prop('onChange')('Rule 1 edited');
    ruleOverlay.find('SingleSelect').prop('onChange')(3);
    ruleOverlay.find('ConditionList').prop('onChange')([]);

    ruleOverlay.setProps({open: false});
    ruleOverlay.update();
    ruleOverlay.setProps({open: true});
    ruleOverlay.update();

    expect(ruleOverlay.find('Input').prop('value')).toEqual('Rule 1');
    expect(ruleOverlay.find('SingleSelect').prop('value')).toEqual(2);
    expect(ruleOverlay.find('ConditionList').prop('value')).toEqual(conditions);

    ruleOverlay.setProps({open: false});
    ruleOverlay.update();
    ruleOverlay.setProps({open: true, value: undefined});
    ruleOverlay.update();

    expect(ruleOverlay.find('Input').prop('value')).toEqual(undefined);
    expect(ruleOverlay.find('SingleSelect').prop('value')).toEqual(undefined);
    expect(ruleOverlay.find('ConditionList').prop('value')).toEqual([]);
});

test('Call confirm with the current values', () => {
    const confirmSpy = jest.fn();

    const ruleOverlay = shallow(
        <RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />
    );

    ruleOverlay.find('Input').prop('onChange')('Rule 11');
    ruleOverlay.find('SingleSelect').prop('onChange')(2);
    ruleOverlay.find('ConditionList').prop('onChange')([
        {
            condition: {
                parameter: 'asdf',
                value: 'jklö',
            },
            type: 'query_string',
        },
    ]);

    ruleOverlay.find('Overlay').prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith({
        conditions: [
            {
                condition: {
                    parameter: 'asdf',
                    value: 'jklö',
                },
                type: 'query_string',
            },
        ],
        frequency: 2,
        title: 'Rule 11',
    });
});

test('Show error if empty fields are confirmed', () => {
    const confirmSpy = jest.fn();

    const ruleOverlay = shallow(
        <RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />
    );

    expect(ruleOverlay.find('Field[label="sulu_admin.title"]').prop('error')).toEqual(undefined);
    expect(ruleOverlay.find('Field[label="sulu_audience_targeting.assigned_at"]').prop('error')).toEqual(undefined);

    ruleOverlay.find('Overlay').prop('onConfirm')();

    expect(ruleOverlay.find('Field[label="sulu_admin.title"]').prop('error')).toEqual('sulu_admin.error_required');
    expect(ruleOverlay.find('Field[label="sulu_audience_targeting.assigned_at"]').prop('error'))
        .toEqual('sulu_admin.error_required');

    expect(confirmSpy).not.toBeCalled();
});

test('Call onClose callback when overlay is closed', () => {
    const closeSpy = jest.fn();

    const ruleOverlay = shallow(<RuleOverlay onClose={closeSpy} onConfirm={jest.fn()} open={true} value={undefined} />);
    ruleOverlay.find('Overlay').prop('onClose')();

    expect(closeSpy).toBeCalledWith();
});
