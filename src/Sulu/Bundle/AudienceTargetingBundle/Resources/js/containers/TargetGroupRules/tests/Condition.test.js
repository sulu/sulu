// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Button, SingleSelect} from 'sulu-admin-bundle/components';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import ruleRegistry from '../registries/ruleRegistry';
import ruleTypeRegistry from '../registries/ruleTypeRegistry';
import Condition from '../Condition';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const Button = jest.fn(() => null);
    const SingleSelect: any = jest.fn(() => null);
    SingleSelect.Option = jest.fn(() => null);

    return {Button, SingleSelect};
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

beforeEach(() => {
    ruleRegistry.getAll.mockReturnValue({});
});

const defaultProps = {
    index: 1,
    onChange: jest.fn(),
    onRemove: jest.fn(),
    value: {condition: {}, type: undefined},
};

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

    render(
        <Condition
            {...defaultProps}
            value={{condition: {}, type: 'browser'}}
        />
    );

    const singleSelectProps: any = getLatestMockProps((SingleSelect: any));
    const optionNodes = React.Children.toArray(singleSelectProps.children);
    expect(singleSelectProps.value).toEqual('browser');
    expect(optionNodes[0].props.children).toEqual('Browser');
    expect(optionNodes[0].props.value).toEqual('browser');
});

test('Call onRemove callback if remove icon is clicked', () => {
    const removeSpy = jest.fn();
    render(
        <Condition
            {...defaultProps}
            index={5}
            onRemove={removeSpy}
            value={{condition: {}, type: undefined}}
        />
    );

    const buttonProps: any = getLatestMockProps((Button: any));
    buttonProps.onClick();

    expect(removeSpy).toBeCalledWith(5);
});

test('Call onChange callback if rule type changes', () => {
    const changeSpy = jest.fn();
    render(
        <Condition
            {...defaultProps}
            index={5}
            onChange={changeSpy}
            value={{condition: {test: 'value'}, type: undefined}}
        />
    );

    const singleSelectProps: any = getLatestMockProps((SingleSelect: any));
    singleSelectProps.onChange('browser');

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
        }
    });

    const changeSpy = jest.fn();
    render(
        <Condition
            {...defaultProps}
            index={5}
            onChange={changeSpy}
            value={{condition: {}, type: 'browser'}}
        />
    );

    const ruleTypeProps: any = getLatestMockProps((RuleType: any));
    ruleTypeProps.onChange({test: 'value'});

    expect(changeSpy).toBeCalledWith({condition: {test: 'value'}, type: 'browser'}, 5);
});
