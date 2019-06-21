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

test('Write passed values to input and single select when overlay is opened', () => {
    const ruleOverlay = shallow(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions: [], frequency: 2, title: 'Rule 1'}}
        />
    );

    ruleOverlay.setProps({open: true});
    ruleOverlay.update();

    expect(ruleOverlay.find('Input').prop('value')).toEqual('Rule 1');
    expect(ruleOverlay.find('SingleSelect').prop('value')).toEqual(2);

    ruleOverlay.find('Input').prop('onChange')('Rule 1 edited');
    ruleOverlay.find('SingleSelect').prop('onChange')(3);

    ruleOverlay.setProps({open: false});
    ruleOverlay.update();
    ruleOverlay.setProps({open: true});
    ruleOverlay.update();

    expect(ruleOverlay.find('Input').prop('value')).toEqual('Rule 1');
    expect(ruleOverlay.find('SingleSelect').prop('value')).toEqual(2);

    ruleOverlay.setProps({open: false});
    ruleOverlay.update();
    ruleOverlay.setProps({open: true, value: undefined});
    ruleOverlay.update();

    expect(ruleOverlay.find('Input').prop('value')).toEqual(undefined);
    expect(ruleOverlay.find('SingleSelect').prop('value')).toEqual(undefined);
});

test('Call confirm with the current values', () => {
    const confirmSpy = jest.fn();

    const ruleOverlay = shallow(
        <RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />
    );

    ruleOverlay.find('Input').prop('onChange')('Rule 11');
    ruleOverlay.find('SingleSelect').prop('onChange')(2);

    ruleOverlay.find('Overlay').prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith({
        conditions: [],
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
