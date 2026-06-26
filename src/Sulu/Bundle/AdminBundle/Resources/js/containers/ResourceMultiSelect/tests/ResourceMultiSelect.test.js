// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceMultiSelect from '../ResourceMultiSelect';
import ResourceListStore from '../../../stores/ResourceListStore';

let mockMultiSelectProps: Object = {};

const mockReact = require('react');

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator');

jest.mock('../../../components/Loader', () => jest.fn((props) => (
    mockReact.createElement('div', {'data-size': props.size, 'data-testid': 'loader'})
)));

jest.mock('../../../components/MultiSelect', () => {
    const MultiSelectMock: any = jest.fn((props) => {
        mockMultiSelectProps = props;

        return mockReact.createElement(
            'div',
            {
                'data-disabled': props.disabled ? 'true' : 'false',
                'data-testid': 'multi-select',
            },
            props.children,
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'change',
                    onClick: () => props.onChange([5, 99]),
                    type: 'button',
                },
                'Change'
            ),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'close',
                    onClick: props.onClose,
                    type: 'button',
                },
                'Close'
            )
        );
    });

    MultiSelectMock.Option = (props) => (
        mockReact.createElement(
            'div',
            {
                'data-testid': 'multi-select-option',
                'data-value': props.value,
            },
            props.children
        )
    );

    return MultiSelectMock;
});

beforeEach(() => {
    jest.clearAllMocks();
    mockMultiSelectProps = {};

    mockResourceListStore([
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
    ]);
});

function mockResourceListStore(data, loading = false) {
    (ResourceListStore: any).mockImplementation(function(resourceKey, parameters, idProperty) {
        this.resourceKey = resourceKey;
        this.parameters = parameters;
        this.idProperty = idProperty;
        this.loading = loading;
        this.data = data;
    });
}

test('Render with data', () => {
    render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: ''}, 'id');
    expect(screen.getByText('Test ABC')).toBeInTheDocument();
    expect(screen.getByText('Test DEF')).toBeInTheDocument();
    expect(screen.getByText('Test XYZ')).toBeInTheDocument();
    expect(mockMultiSelectProps.values).toEqual([5, 99]);
});

test('Render in disabled state', () => {
    render(
        <ResourceMultiSelect
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: ''}, 'id');
    expect(screen.getByTestId('multi-select')).toHaveAttribute('data-disabled', 'true');
});

test('Render in loading state', () => {
    mockResourceListStore(undefined, true);

    render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(screen.getByTestId('loader')).toHaveAttribute('data-size', '30');
});

test('Pass requestParameters', () => {
    const requestParameters = {'testOption': 'testValue'};

    render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: '', testOption: 'testValue'}, 'id');
});

test('Pass requestParameters when requestParameters props changed', () => {
    mockResourceListStore([
        {
            'id': 2,
            'name': 'Test ABC',
            'someOtherProperty': 'No no',
        },
    ]);

    const requestParameters1 = {};
    const requestParameters2 = {'testOption': 'testValue'};

    const {rerender} = render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters1}
            resourceKey="test"
            values={undefined}
        />
    );

    rerender(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters2}
            resourceKey="test"
            values={undefined}
        />
    );

    expect((ResourceListStore: any).mock.calls).toEqual([
        ['test', {limit: ''}, 'id'],
        ['test', {limit: '', testOption: 'testValue'}, 'id'],
    ]);
});

test('Pass requestParameters when resourceKey props changed', () => {
    mockResourceListStore([
        {
            'id': 2,
            'name': 'Test ABC',
            'someOtherProperty': 'No no',
        },
    ]);

    const {rerender} = render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test1"
            values={undefined}
        />
    );

    rerender(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test2"
            values={undefined}
        />
    );

    expect((ResourceListStore: any).mock.calls).toEqual([
        ['test1', {limit: ''}, 'id'],
        ['test2', {limit: ''}, 'id'],
    ]);
});

test('The component should trigger the change callback', async() => {
    const onChangeSpy = jest.fn();

    render(
        <ResourceMultiSelect
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

    await userEvent.click(screen.getByLabelText('change'));

    expect(onChangeSpy).toHaveBeenCalledWith([5, 99], expectedValues);
});

test('The component should trigger the close callback', async() => {
    mockResourceListStore([
        {
            'id': 2,
            'name': 'Test ABC',
            'someOtherProperty': 'No no',
        },
    ]);

    const closeSpy = jest.fn();

    render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            onClose={closeSpy}
            resourceKey="test"
            values={[99]}
        />
    );

    expect(closeSpy).not.toHaveBeenCalled();

    await userEvent.click(screen.getByLabelText('close'));

    expect(closeSpy).toHaveBeenCalled();
});
