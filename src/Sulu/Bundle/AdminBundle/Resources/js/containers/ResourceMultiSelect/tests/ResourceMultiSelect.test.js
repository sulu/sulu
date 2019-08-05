// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import ResourceMultiSelect from '../ResourceMultiSelect';
import MultiSelectComponent from '../../../components/MultiSelect';
import ResourceListStore from '../../../stores/ResourceListStore';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render in loading state', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = true;
        this.data = undefined;
    });

    expect(render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    )).toMatchSnapshot();
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

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(resourceMultiSelect.render()).toMatchSnapshot();
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

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(resourceMultiSelect.render()).toMatchSnapshot();
});

test('Render with data and apiOptions', () => {
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

    const apiOptions = {'testOption': 'testValue'};

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            apiOptions={apiOptions}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', apiOptions);
    expect(resourceMultiSelect.render()).toMatchSnapshot();
});

test('Render with data and apiOptions when apiOptions props changed', () => {
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

    const apiOptions1 = {};
    const apiOptions2 = {'testOption': 'testValue'};

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            apiOptions={apiOptions1}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    resourceMultiSelect.setProps({
        apiOptions: apiOptions2,
        displayProperty: 'name',
        onChange: jest.fn(),
        resourceKey: 'test',
        values: undefined,
    });

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test', apiOptions1],
        ['test', apiOptions2],
    ]);
});

test('Render with data and apiOptions when resourceKey props changed', () => {
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

    const apiOptions = {};

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            apiOptions={apiOptions}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test1"
            values={undefined}
        />
    );

    resourceMultiSelect.setProps({
        apiOptions: apiOptions,
        displayProperty: 'name',
        onChange: jest.fn(),
        resourceKey: 'test2',
        values: undefined,
    });

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test1', apiOptions],
        ['test2', apiOptions],
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

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(resourceMultiSelect.render()).toMatchSnapshot();
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
    const resourceMultiSelect = shallow(
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

    resourceMultiSelect.find(MultiSelectComponent).props().onChange([5, 99]);
    expect(onChangeSpy).toHaveBeenCalledWith([5, 99], expectedValues);
});
