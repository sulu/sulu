// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {Form, Input, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import ConditionList from '../ConditionList';
import RuleOverlay from '../RuleOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const FormMock: any = jest.fn(({children}) => <div>{children}</div>);
    FormMock.Field = jest.fn(({children}) => <div>{children}</div>);
    const SingleSelectMock: any = jest.fn(() => null);
    SingleSelectMock.Option = jest.fn(() => null);

    return {
        Form: FormMock,
        Input: jest.fn(() => null),
        Overlay: jest.fn(({children}) => <div>{children}</div>),
        SingleSelect: SingleSelectMock,
    };
});

jest.mock('../ConditionList', () => jest.fn(() => null));

function getLatestOverlayProps() {
    const calls = (Overlay: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestInputProps() {
    const calls = (Input: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestSingleSelectProps() {
    const calls = (SingleSelect: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestConditionListProps() {
    const calls = (ConditionList: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestFieldProps(label: string) {
    const fieldCalls = (Form.Field: any).mock.calls
        .map(([props]) => props)
        .reverse();

    const fieldProps = fieldCalls.find((props) => props.label === label);
    if (!fieldProps) {
        throw new Error('Expected Form.Field with label "' + label + '"');
    }

    return fieldProps;
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render RuleOverlay without value', () => {
    render(<RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />);
    expect(getLatestInputProps().value).toEqual(undefined);
    expect(getLatestSingleSelectProps().value).toEqual(undefined);
    expect(getLatestConditionListProps().value).toEqual([]);
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

    const {rerender} = render(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    expect(getLatestInputProps().value).toEqual('Rule 1');
    expect(getLatestSingleSelectProps().value).toEqual(2);
    expect(getLatestConditionListProps().value).toEqual(conditions);

    act(() => {
        getLatestInputProps().onChange('Rule 1 edited');
    });
    act(() => {
        getLatestSingleSelectProps().onChange(3);
    });
    act(() => {
        getLatestConditionListProps().onChange([]);
    });

    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );
    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );

    expect(getLatestInputProps().value).toEqual('Rule 1');
    expect(getLatestSingleSelectProps().value).toEqual(2);
    expect(getLatestConditionListProps().value).toEqual(conditions);

    rerender(
        <RuleOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            value={{conditions, frequency: 2, title: 'Rule 1'}}
        />
    );
    rerender(<RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />);

    expect(getLatestInputProps().value).toEqual(undefined);
    expect(getLatestSingleSelectProps().value).toEqual(undefined);
    expect(getLatestConditionListProps().value).toEqual([]);
});

test('Call confirm with the current values', () => {
    const confirmSpy = jest.fn();

    render(
        <RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />
    );

    act(() => {
        getLatestInputProps().onChange('Rule 11');
    });
    act(() => {
        getLatestSingleSelectProps().onChange(2);
    });
    act(() => {
        getLatestConditionListProps().onChange([
            {
                condition: {
                    parameter: 'asdf',
                    value: 'jklö',
                },
                type: 'query_string',
            },
        ]);
    });

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

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

    render(
        <RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />
    );

    expect(getLatestFieldProps('sulu_admin.title').error).toEqual(undefined);
    expect(getLatestFieldProps('sulu_audience_targeting.assigned_at').error).toEqual(undefined);

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

    expect(getLatestFieldProps('sulu_admin.title').error).toEqual('sulu_admin.error_required');
    expect(getLatestFieldProps('sulu_audience_targeting.assigned_at').error)
        .toEqual('sulu_admin.error_required');

    expect(confirmSpy).not.toBeCalled();
});

test('Call onClose callback when overlay is closed', () => {
    const closeSpy = jest.fn();

    render(<RuleOverlay onClose={closeSpy} onConfirm={jest.fn()} open={true} value={undefined} />);
    getLatestOverlayProps().onClose();

    expect(closeSpy).toBeCalledWith();
});
