// @flow
/* eslint-disable react/jsx-no-bind */
import React from 'react';
import {render, screen} from '@testing-library/react';
import ResourceCheckboxGroup from '../ResourceCheckboxGroup';
import CheckboxComponent, {CheckboxGroup as CheckboxGroupComponent} from '../../../components/Checkbox';
import ResourceListStore from '../../../stores/ResourceListStore';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../components/Loader', () => jest.fn(function Loader() {
    return <div data-testid="loader" />;
}));

jest.mock('../../../components/Checkbox', () => {
    const React = require('react');

    const Checkbox: any = jest.fn(function Checkbox(props) {
        function handleInputChange(event) {
            if (props.onChange) {
                props.onChange(event.currentTarget.checked, props.value);
            }
        }

        return (
            <label>
                <input
                    aria-label={String(props.children)}
                    checked={!!props.checked}
                    disabled={props.disabled}
                    onChange={handleInputChange}
                    type="checkbox"
                />
                {props.children}
            </label>
        );
    });

    const CheckboxGroup: any = jest.fn(function CheckboxGroup(props) {
        function handleGroupChange(checked, changedValue) {
            if (checked && changedValue) {
                props.onChange([...props.values, changedValue]);
                return;
            }

            props.onChange(props.values.filter((value) => value !== changedValue));
        }

        return (
            <div>
                {React.Children.map(props.children, (child) => React.cloneElement(child, {
                    checked: props.values.includes(child.props.value),
                    disabled: props.disabled,
                    onChange: handleGroupChange,
                }))}
            </div>
        );
    });

    return {
        __esModule: true,
        CheckboxGroup,
        default: Checkbox,
    };
});

function getLatestCheckboxGroupProps() {
    const calls = (CheckboxGroupComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestCheckboxProps(value) {
    const calls = (CheckboxComponent: any).mock.calls;
    const matchingCalls = calls.map(([props]) => props).filter((props) => props.value === value);

    return matchingCalls[matchingCalls.length - 1];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render with data', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
            {
                'id': 5,
                'name': 'Test DEF',
                'someOtherProperty': 'YES YES',
            },
            {
                'id': 99,
                'name': 'Test XYZ',
                'someOtherProperty': 'maybe maybe',
            },
        ];
    });

    const {asFragment} = render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(asFragment()).toMatchSnapshot();
});

test('Render in disabled state', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
            {
                'id': 5,
                'name': 'Test DEF',
                'someOtherProperty': 'YES YES',
            },
        ];
    });

    render(
        <ResourceCheckboxGroup
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(getLatestCheckboxGroupProps().disabled).toEqual(true);
});

test('Render in loading state', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = true;
        this.data = undefined;
    });

    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(screen.getByTestId('loader')).toBeInTheDocument();
});

test('Pass requestParameters', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
            {
                'id': 5,
                'name': 'Test DEF',
                'someOtherProperty': 'YES YES',
            },
            {
                'id': 99,
                'name': 'Test XYZ',
                'someOtherProperty': 'maybe maybe',
            },
        ];
    });

    const requestParameters = {'testOption': 'testValue'};

    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', requestParameters);
});

test('Pass requestParameters when requestParameters props changed', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
        ];
    });

    const requestParameters1 = {};
    const requestParameters2 = {'testOption': 'testValue'};

    const {rerender} = render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters1}
            resourceKey="test"
            values={undefined}
        />
    );

    rerender(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters2}
            resourceKey="test"
            values={undefined}
        />
    );

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test', requestParameters1],
        ['test', requestParameters2],
    ]);
});

test('Pass requestParameters when resourceKey props changed', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
        ];
    });

    const requestParameters = {};

    const {rerender} = render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters}
            resourceKey="test1"
            values={undefined}
        />
    );

    rerender(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters}
            resourceKey="test2"
            values={undefined}
        />
    );

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test1', requestParameters],
        ['test2', requestParameters],
    ]);
});

test('Render with values', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
            {
                'id': 5,
                'name': 'Test DEF',
                'someOtherProperty': 'YES YES',
            },
            {
                'id': 99,
                'name': 'Test XYZ',
                'someOtherProperty': 'maybe maybe',
            },
        ];
    });

    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(getLatestCheckboxProps(2).checked).toEqual(false);
    expect(getLatestCheckboxProps(5).checked).toEqual(true);
    expect(getLatestCheckboxProps(99).checked).toEqual(true);
});

test('The component should trigger the change callback', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                'id': 2,
                'name': 'Test ABC',
                'someOtherProperty': 'No no',
            },
            {
                'id': 5,
                'name': 'Test DEF',
                'someOtherProperty': 'YES YES',
            },
            {
                'id': 99,
                'name': 'Test XYZ',
                'someOtherProperty': 'maybe maybe',
            },
        ];
    });

    const onChangeSpy = jest.fn();
    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={onChangeSpy}
            resourceKey="test"
            values={[99]}
        />
    );

    const expectedValues = [
        {
            'id': 5,
            'name': 'Test DEF',
            'someOtherProperty': 'YES YES',
        },
        {
            'id': 99,
            'name': 'Test XYZ',
            'someOtherProperty': 'maybe maybe',
        },
    ];

    getLatestCheckboxGroupProps().onChange([5, 99]);
    expect(onChangeSpy).toHaveBeenCalledWith([5, 99], expectedValues);
});
