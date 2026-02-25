// @flow
import React from 'react';
import {render} from '@testing-library/react';
import findMockCallArg from 'sulu-admin-bundle/utils/TestHelper/findMockCallArg';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import RuleOverlay from '../RuleOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const Form: any = jest.fn(({children}) => <div>{children}</div>);
    Form.Field = jest.fn(({children, error, label}) => (
        <div data-error={error} data-label={label}>
            {children}
        </div>
    ));

    const SingleSelect: any = jest.fn(({children}) => <div>{children}</div>);
    SingleSelect.Option = jest.fn(({children}) => <div>{children}</div>);

    return {
        Form,
        Input: jest.fn(() => null),
        Overlay: jest.fn(({children}) => <div>{children}</div>),
        SingleSelect,
    };
});

jest.mock('../ConditionList', () => jest.fn(() => null));

const componentsModule = ((jest.requireMock('sulu-admin-bundle/components'): any): {
    Form: {
        Field: {
            mock: {calls: Array<[Object]>},
            ...
        },
        ...
    },
    Input: {
        mock: {calls: Array<[Object]>},
        ...
    },
    Overlay: {
        mock: {calls: Array<[Object]>},
        ...
    },
    SingleSelect: {
        mock: {calls: Array<[Object]>},
        ...
    },
    ...
});
const conditionListComponent = ((jest.requireMock('../ConditionList'): any): {
    mock: {calls: Array<[Object]>},
    ...
});
const formFieldComponent = componentsModule.Form.Field;
const inputComponent = componentsModule.Input;
const overlayComponent = componentsModule.Overlay;
const singleSelectComponent = componentsModule.SingleSelect;

const getFieldProps = (label: string) => {
    return findMockCallArg(formFieldComponent, ([props]) => props.label === label);
};

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render RuleOverlay without value', () => {
    const {asFragment} = render(
        <RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />
    );
    expect(asFragment()).toMatchSnapshot();
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

    expect(getLatestMockProps(inputComponent).value).toEqual('Rule 1');
    expect(getLatestMockProps(singleSelectComponent).value).toEqual(2);
    expect(getLatestMockProps(conditionListComponent).value).toEqual(conditions);

    getLatestMockProps(inputComponent).onChange('Rule 1 edited');
    getLatestMockProps(singleSelectComponent).onChange(3);
    getLatestMockProps(conditionListComponent).onChange([]);

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

    expect(getLatestMockProps(inputComponent).value).toEqual('Rule 1');
    expect(getLatestMockProps(singleSelectComponent).value).toEqual(2);
    expect(getLatestMockProps(conditionListComponent).value).toEqual(conditions);

    rerender(<RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={false} value={undefined} />);
    rerender(<RuleOverlay onClose={jest.fn()} onConfirm={jest.fn()} open={true} value={undefined} />);

    expect(getLatestMockProps(inputComponent).value).toEqual(undefined);
    expect(getLatestMockProps(singleSelectComponent).value).toEqual(undefined);
    expect(getLatestMockProps(conditionListComponent).value).toEqual([]);
});

test('Call confirm with the current values', () => {
    const confirmSpy = jest.fn();

    render(<RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />);

    getLatestMockProps(inputComponent).onChange('Rule 11');
    getLatestMockProps(singleSelectComponent).onChange(2);
    getLatestMockProps(conditionListComponent).onChange([
        {
            condition: {
                parameter: 'asdf',
                value: 'jklö',
            },
            type: 'query_string',
        },
    ]);

    getLatestMockProps(overlayComponent).onConfirm();

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

    render(<RuleOverlay onClose={jest.fn()} onConfirm={confirmSpy} open={true} value={undefined} />);

    expect(getFieldProps('sulu_admin.title').error).toEqual(undefined);
    expect(getFieldProps('sulu_audience_targeting.assigned_at').error).toEqual(undefined);

    getLatestMockProps(overlayComponent).onConfirm();

    expect(getFieldProps('sulu_admin.title').error).toEqual('sulu_admin.error_required');
    expect(getFieldProps('sulu_audience_targeting.assigned_at').error).toEqual('sulu_admin.error_required');

    expect(confirmSpy).not.toBeCalled();
});

test('Call onClose callback when overlay is closed', () => {
    const closeSpy = jest.fn();

    render(<RuleOverlay onClose={closeSpy} onConfirm={jest.fn()} open={true} value={undefined} />);
    getLatestMockProps(overlayComponent).onClose();

    expect(closeSpy).toBeCalledWith();
});
