// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import ResourceMultiSelect from '../ResourceMultiSelect';
import MultiSelectComponent from '../../../components/MultiSelect';
import ResourceListStore from '../../../stores/ResourceListStore';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

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
            values={[5, 99]}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {limit: ''}, 'id');
    expect(resourceMultiSelect.render()).toMatchSnapshot();
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

    const resourceMultiSelect = shallow(
        <ResourceMultiSelect
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {limit: ''}, 'id');
    expect(resourceMultiSelect.find('MultiSelect').prop('disabled')).toEqual(true);
});

test('Render in loading state', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = true;
        this.data = undefined;
    });

    const resourceMultiSelect = shallow(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(resourceMultiSelect.find('Loader')).toHaveLength(1);
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

    mount(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {limit: '', testOption: 'testValue'}, 'id');
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

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            requestParameters={requestParameters1}
            resourceKey="test"
            values={undefined}
        />
    );

    resourceMultiSelect.setProps({
        requestParameters: requestParameters2,
        displayProperty: 'name',
        onChange: jest.fn(),
        resourceKey: 'test',
        values: undefined,
    });

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test', {limit: ''}, 'id'],
        ['test', {limit: '', testOption: 'testValue'}, 'id'],
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

    const resourceMultiSelect = mount(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test1"
            values={undefined}
        />
    );

    resourceMultiSelect.setProps({
        displayProperty: 'name',
        onChange: jest.fn(),
        resourceKey: 'test2',
        values: undefined,
    });

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test1', {limit: ''}, 'id'],
        ['test2', {limit: ''}, 'id'],
    ]);
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

test('The component should trigger the close callback', () => {
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

    const closeSpy = jest.fn();

    const resourceMultiSelect = shallow(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            onClose={closeSpy}
            resourceKey="test"
            values={[99]}
        />
    );

    expect(closeSpy).not.toBeCalled();
    resourceMultiSelect.find(MultiSelectComponent).prop('onClose')();
    expect(closeSpy).toBeCalled();
});
