// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import ConditionList from '../ConditionList';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render an empty ConditionList', () => {
    const value = [];
    expect(render(<ConditionList onChange={jest.fn()} value={value} />)).toMatchSnapshot();
});

test('Add a new Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
    ];

    const changeSpy = jest.fn();

    const conditionList = shallow(<ConditionList onChange={changeSpy} value={value} />);

    conditionList.find('Button[icon="su-plus"]').prop('onClick')();

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}, {condition: {}, type: undefined}]);
});

test('Edit an existing Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    const conditionList = shallow(<ConditionList onChange={changeSpy} value={value} />);
    conditionList.find('Condition').at(1).prop('onChange')({condition: {test: 'value'}, type: 'test'}, 1);

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}, {condition: {test: 'value'}, type: 'test'}]);
});

test('Remove an existing Condition', () => {
    const value = [
        {condition: {}, type: 'browser'},
        {condition: {}, type: undefined},
    ];

    const changeSpy = jest.fn();

    const conditionList = shallow(<ConditionList onChange={changeSpy} value={value} />);
    conditionList.find('Condition').at(1).prop('onRemove')(1);

    expect(changeSpy).toBeCalledWith([{condition: {}, type: 'browser'}]);
});
