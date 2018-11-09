// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import MultiSelect from '../MultiSelect';
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
        <MultiSelect
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

    const multiSelect = mount(
        <MultiSelect
            disabled={true}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(multiSelect.render()).toMatchSnapshot();
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

    const multiSelect = mount(
        <MultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(multiSelect.render()).toMatchSnapshot();
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

    const multiSelect = mount(
        <MultiSelect
            apiOptions={apiOptions}
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', apiOptions);
    expect(multiSelect.render()).toMatchSnapshot();
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

    const multiSelect = mount(
        <MultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', {});
    expect(multiSelect.render()).toMatchSnapshot();
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
    const multiSelect = shallow(
        <MultiSelect
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

    multiSelect.find(MultiSelectComponent).props().onChange([5, 99]);
    expect(onChangeSpy).toHaveBeenCalledWith([5, 99], expectedValues);
});
