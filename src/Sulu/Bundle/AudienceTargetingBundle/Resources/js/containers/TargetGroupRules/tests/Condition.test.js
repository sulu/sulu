// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Button, SingleSelect} from 'sulu-admin-bundle/components';
import ruleRegistry from '../registries/ruleRegistry';
import ruleTypeRegistry from '../registries/ruleTypeRegistry';
import Condition from '../Condition';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const SingleSelectMock = jest.fn(() => null);
    (SingleSelectMock: any).Option = jest.fn(() => null);

    return {
        Button: jest.fn(() => null),
        SingleSelect: SingleSelectMock,
    };
});

jest.mock('../registries/ruleRegistry', () => {
    const getAllMock = jest.fn();

    return {
        getAll: getAllMock,
        get: (key) => getAllMock()[key],
    };
});

jest.mock('../registries/ruleTypeRegistry', () => ({
    get: jest.fn(),
}));

function getLatestButtonProps() {
    const calls = (Button: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestSingleSelectProps() {
    const calls = (SingleSelect: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getSingleSelectOptionProps(index: number) {
    const selectProps = getLatestSingleSelectProps();
    const options = React.Children.toArray(selectProps.children);
    return options[index].props;
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a condition', () => {
    ruleRegistry.getAll.mockReturnValue({
        browser: {
            name: 'Browser',
            type: {
                name: 'select',
                options: {},
            },
        },
    });
    const RuleType = jest.fn(() => null);
    ruleTypeRegistry.get.mockReturnValue(RuleType);

    const value = {condition: {}, type: 'browser'};
    render(<Condition index={1} onChange={jest.fn()} onRemove={jest.fn()} value={value} />);

    expect(getLatestSingleSelectProps().value).toEqual('browser');
    expect(getSingleSelectOptionProps(0).value).toEqual('browser');
    expect(getSingleSelectOptionProps(0).children).toEqual('Browser');

    const ruleTypeProps = RuleType.mock.calls[0][0];
    expect(ruleTypeProps.options).toEqual({});
    expect(ruleTypeProps.value).toEqual({});
});

test('Call onRemove callback if remove icon is clicked', () => {
    const removeSpy = jest.fn();
    const value = {condition: {}, type: undefined};

    render(<Condition index={5} onChange={jest.fn()} onRemove={removeSpy} value={value} />);
    getLatestButtonProps().onClick();

    expect(removeSpy).toBeCalledWith(5);
});

test('Call onChange callback if rule type changes', () => {
    const changeSpy = jest.fn();
    const value = {condition: {test: 'value'}, type: undefined};

    render(<Condition index={5} onChange={changeSpy} onRemove={jest.fn()} value={value} />);
    getLatestSingleSelectProps().onChange('browser');

    expect(changeSpy).toBeCalledWith({condition: {test: 'value'}, type: 'browser'}, 5);
});

test('Call onChange callback if condition changes', () => {
    ruleRegistry.getAll.mockReturnValue({
        browser: {
            name: 'Browser',
            type: {
                name: 'select',
                options: {},
            },
        },
    });

    const RuleType = jest.fn(() => null);
    ruleTypeRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'select':
                return RuleType;
            default:
                return undefined;
        }
    });

    const changeSpy = jest.fn();
    const value = {condition: {}, type: 'browser'};

    render(<Condition index={5} onChange={changeSpy} onRemove={jest.fn()} value={value} />);
    const ruleTypeProps = RuleType.mock.calls[0][0];
    ruleTypeProps.onChange({test: 'value'});

    expect(changeSpy).toBeCalledWith({condition: {test: 'value'}, type: 'browser'}, 5);
});
