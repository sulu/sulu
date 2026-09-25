// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceCheckboxGroup from '../ResourceCheckboxGroup';
import ResourceListStore from '../../../stores/ResourceListStore';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator');

function mockResourceListStore(data, loading = false) {
    (ResourceListStore: any).mockImplementation(function() {
        this.loading = loading;
        this.data = data;
    });
}

test('Render with data', () => {
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

    const {asFragment} = render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {});
    expect(asFragment()).toMatchSnapshot();
});

test('Render in disabled state', () => {
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
    ]);

    render(
        <ResourceCheckboxGroup
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {});
    expect(screen.getByLabelText('Test ABC')).toBeDisabled();
    expect(screen.getByLabelText('Test DEF')).toBeDisabled();
});

test('Render in loading state', () => {
    mockResourceListStore(undefined, true);

    const {asFragment} = render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Pass requestParameters', () => {
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

    expect(ResourceListStore).toHaveBeenCalledWith('test', requestParameters);
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

    expect((ResourceListStore: any).mock.calls).toEqual([
        ['test', requestParameters1],
        ['test', requestParameters2],
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

    expect((ResourceListStore: any).mock.calls).toEqual([
        ['test1', requestParameters],
        ['test2', requestParameters],
    ]);
});

test('Render with values', () => {
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

    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(screen.getByLabelText('Test ABC')).not.toBeChecked();
    expect(screen.getByLabelText('Test DEF')).toBeChecked();
    expect(screen.getByLabelText('Test XYZ')).toBeChecked();
});

test('The component should trigger the change callback', async() => {
    const user = userEvent.setup();
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

    const onChangeSpy = jest.fn();
    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={onChangeSpy}
            resourceKey="test"
            values={[5]}
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

    await user.click(screen.getByLabelText('Test XYZ'));

    expect(onChangeSpy).toHaveBeenCalledWith([5, 99], expectedValues);
});
