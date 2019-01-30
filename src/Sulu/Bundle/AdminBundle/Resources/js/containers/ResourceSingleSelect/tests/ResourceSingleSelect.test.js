// @flow
import React from 'react';
import {shallow, mount, render} from 'enzyme';
import ResourceListStore from '../../../stores/ResourceListStore';
import ResourceSingleSelect from '../ResourceSingleSelect';

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
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    )).toMatchSnapshot();
});

test('Render in disabled state', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [];
    });

    expect(render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    )).toMatchSnapshot();
});

test('Render with data', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
            {
                id: 2,
                name: 'Test 2',
            },
        ];
    });

    const resourceSingleSelect = mount(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={undefined}
        />
    );

    resourceSingleSelect.find('DisplayValue').simulate('click');
    resourceSingleSelect.update();

    expect(resourceSingleSelect.render()).toMatchSnapshot();
    expect(resourceSingleSelect.find('Menu').render()).toMatchSnapshot();
});

test('Render in value', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
        ];
    });

    expect(render(
        <ResourceSingleSelect
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    )).toMatchSnapshot();
});

test('Trigger the change callback when the selection changes', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {
                id: 1,
                name: 'Test 1',
            },
            {
                id: 2,
                name: 'Test 2',
            },
        ];
    });

    const changeSpy = jest.fn();

    const singleResourceSelect = shallow(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    singleResourceSelect.find('SingleSelect').prop('onChange')(2);
    expect(changeSpy).toHaveBeenCalledWith(2);
});
