// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import TargetGroupRule from '../../fields/TargetGroupRules';

let mockTargetGroupRulesProps: Object = {};

const mockReact = require('react');

jest.mock('../../../../containers/TargetGroupRules', () => jest.fn((props) => {
    mockTargetGroupRulesProps = props;

    return mockReact.createElement(
        'button',
        {
            onClick: () => props.onChange([{}]),
            type: 'button',
        },
        'target-group-rules'
    );
}));

beforeEach(() => {
    mockTargetGroupRulesProps = {};
});

test('Pass a default value of an empty array to the component', () => {
    render(<TargetGroupRule {...fieldTypeDefaultProps} />);

    expect(mockTargetGroupRulesProps.value).toEqual([]);
});

test('Pass the given value to the component', () => {
    const value = [];

    render(<TargetGroupRule {...fieldTypeDefaultProps} value={value} />);

    expect(mockTargetGroupRulesProps.value).toBe(value);
});

test('Call onChange and onFinish if value of componetn changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <TargetGroupRule
            {...fieldTypeDefaultProps}
            onChange={changeSpy}
            onFinish={finishSpy}
        />
    );

    await user.click(screen.getByRole('button', {name: 'target-group-rules'}));

    expect(changeSpy).toHaveBeenCalledWith([{}]);
    expect(finishSpy).toHaveBeenCalledWith();
});
