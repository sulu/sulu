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

test('Render with data with editable option', () => {
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
            editable={true}
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

test('Pass apiOptions to ResourceListStore', () => {
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

    const apiOptions = {
        flat: true,
    };

    mount(
        <ResourceSingleSelect
            apiOptions={apiOptions}
            disabled={true}
            displayProperty="name"
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    expect(ResourceListStore).toBeCalledWith('test', apiOptions, 'id');
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

    const resourceSingleSelect = shallow(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    resourceSingleSelect.find('SingleSelect').prop('onChange')(2);
    expect(changeSpy).toHaveBeenCalledWith(2);
});

test('Trigger the change callback with undefined when the reset action is clicked', () => {
    const changeSpy = jest.fn();

    const resourceSingleSelect = shallow(
        <ResourceSingleSelect
            displayProperty="name"
            idProperty="id"
            onChange={changeSpy}
            resourceKey="test"
            value={1}
        />
    );

    resourceSingleSelect.find('Action[children="sulu_admin.please_choose"]').prop('onClick')();
    expect(changeSpy).toHaveBeenCalledWith(undefined);
});

test('Updated data in EditOverlay should disappear when overlay is closed', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {id: 1, name: 'Test1'},
            {id: 2, name: 'Test2'},
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    const resourceSingleSelect = mount(
        <ResourceSingleSelect
            displayProperty="name"
            editable={true}
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    resourceSingleSelect.find('DisplayValue').simulate('click');
    resourceSingleSelect.find('Action[children="sulu_admin.edit"]').prop('onClick')();

    resourceSingleSelect.update();
    resourceSingleSelect.find('EditLine Input').at(0).prop('onChange')('Test1 Update');
    resourceSingleSelect.find('EditLine Button').at(1).prop('onClick')();
    resourceSingleSelect.find('EditOverlay Button[icon="su-plus"]').prop('onClick')();
    resourceSingleSelect.find('EditLine Input').at(1).prop('onChange')('Test3 Update');
    resourceSingleSelect.find('Icon[name="su-times"]').prop('onClick')();

    expect(resourceSingleSelect.instance().resourceListStore.deleteList).not.toBeCalled();
    expect(resourceSingleSelect.instance().resourceListStore.patchList).not.toBeCalled();
});

test('Updated data in EditOverlay should be displayed in Select when overlay is confirmed', () => {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = false;
        this.data = [
            {id: 1, name: 'Test1'},
            {id: 2, name: 'Test2'},
        ];
        this.deleteList = jest.fn();
        this.patchList = jest.fn();
    });

    const resourceSingleSelect = mount(
        <ResourceSingleSelect
            displayProperty="name"
            editable={true}
            idProperty="id"
            onChange={jest.fn()}
            resourceKey="test"
            value={1}
        />
    );

    resourceSingleSelect.find('DisplayValue').simulate('click');
    resourceSingleSelect.find('Action[children="sulu_admin.edit"]').prop('onClick')();

    resourceSingleSelect.update();
    resourceSingleSelect.find('EditLine Input').at(0).prop('onChange')('Test1 Update');
    resourceSingleSelect.find('EditLine Button').at(1).prop('onClick')();
    resourceSingleSelect.find('EditOverlay Button[icon="su-plus"]').prop('onClick')();
    resourceSingleSelect.find('EditLine Input').at(1).prop('onChange')('Test3 Update');
    resourceSingleSelect.find('Button[skin="primary"]').prop('onClick')();

    expect(resourceSingleSelect.instance().resourceListStore.deleteList).toBeCalledWith([2]);
    expect(resourceSingleSelect.instance().resourceListStore.patchList).toBeCalledWith([
        {name: 'Test3 Update'},
        {id: 1, 'name': 'Test1 Update'},
    ]);
});
