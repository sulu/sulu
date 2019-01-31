// @flow
import React from 'react';
import {mount} from 'enzyme';
import ResourceListStore from '../../../stores/ResourceListStore';
import EditOverlay from '../EditOverlay';

jest.mock('../../../stores/ResourceListStore', () => jest.fn(function() {
    this.data = [];
    this.patchList = jest.fn();
    this.deleteList = jest.fn();
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

test('Render data in EditLines', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            id: 1,
            title: 'Test 1',
        },
        {
            id: 2,
            title: 'Test 2',
        },
    ];

    const editOverlay = mount(
        <EditOverlay
            displayProperty="title"
            idProperty="id"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(editOverlay.find('header').render()).toMatchSnapshot();
    expect(editOverlay.find('article .overlay').render()).toMatchSnapshot();
});

test('Render data in EditLines with other properties', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(editOverlay.find('header').render()).toMatchSnapshot();
    expect(editOverlay.find('article .overlay').render()).toMatchSnapshot();
});

test('Should only delete items from  ResourceStoreList if data is only deleted', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const closeSpy = jest.fn();

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    editOverlay.find('EditLine Button').at(0).prop('onClick')();

    editOverlay.find('Button[skin="primary"]').simulate('click');

    expect(resourceListStore.patchList).not.toBeCalled();
    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Should only update ResourceStoreList if data is only changed and not deleted', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const closeSpy = jest.fn();

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    editOverlay.find('EditLine Button').at(0).prop('onClick')();

    editOverlay.find('Button[skin="primary"]').simulate('click');

    expect(resourceListStore.patchList).not.toBeCalled();

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Should update ResourceStoreList if data is changed and confirm button is clicked', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const closeSpy = jest.fn();

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(editOverlay.find('EditLine')).toHaveLength(2);
    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    expect(editOverlay.find('EditLine')).toHaveLength(4);

    editOverlay.find('EditLine Input').at(1).prop('onChange')('Test 2 Update');
    editOverlay.find('EditLine Input').at(2).prop('onChange')('Test 3');
    editOverlay.find('EditLine Input').at(3).prop('onChange')('Test 4');

    editOverlay.find('EditLine Button').at(0).prop('onClick')();

    editOverlay.find('Button[skin="primary"]').simulate('click');

    expect(resourceListStore.patchList).toBeCalledWith([
        {position: 'Test 3'},
        {position: 'Test 4'},
        {position: 'Test 2 Update', uuid: 2},
    ]);

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('An empty field should not be added', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const closeSpy = jest.fn();

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(editOverlay.find('EditLine')).toHaveLength(2);
    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    expect(editOverlay.find('EditLine')).toHaveLength(4);

    editOverlay.find('EditLine Input').at(2).prop('onChange')('Test 3');

    editOverlay.find('EditLine Button').at(0).prop('onClick')();

    editOverlay.find('Button[skin="primary"]').simulate('click');

    expect(resourceListStore.patchList).toBeCalledWith([
        {position: 'Test 3'},
    ]);

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Adding the same field as already existing should not add it', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const closeSpy = jest.fn();

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    editOverlay.find('EditLine Input').at(2).prop('onChange')('Test 2');
    editOverlay.find('EditLine Button').at(0).prop('onClick')();
    editOverlay.find('Button[skin="primary"]').simulate('click');

    expect(resourceListStore.patchList).not.toBeCalledWith();
});

test('Adding the same field twice should add it only once', () => {
    const resourceListStore = new ResourceListStore('accounts');
    resourceListStore.data = [
        {
            uuid: 1,
            position: 'Test 1',
        },
        {
            uuid: 2,
            position: 'Test 2',
        },
    ];

    const closeSpy = jest.fn();

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={closeSpy}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    expect(editOverlay.find('EditLine')).toHaveLength(2);
    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    editOverlay.find('Button[icon="su-plus"]').simulate('click');
    expect(editOverlay.find('EditLine')).toHaveLength(4);

    editOverlay.find('EditLine Input').at(2).prop('onChange')('Test 3');
    editOverlay.find('EditLine Input').at(3).prop('onChange')('Test 3');

    editOverlay.find('EditLine Button').at(0).prop('onClick')();

    editOverlay.find('Button[skin="primary"]').simulate('click');

    expect(resourceListStore.patchList).toBeCalledWith([
        {position: 'Test 3'},
    ]);

    expect(resourceListStore.deleteList).toBeCalledWith([1]);
});

test('Call disposer when component unmounts', () => {
    const resourceListStore = new ResourceListStore('accounts');

    const editOverlay = mount(
        <EditOverlay
            displayProperty="position"
            idProperty="uuid"
            onClose={jest.fn()}
            open={true}
            resourceListStore={resourceListStore}
            title="Add something"
        />
    );

    const updateDataDisposerSpy = jest.fn();
    editOverlay.instance().updateDataDisposer = updateDataDisposerSpy;

    editOverlay.unmount();

    expect(updateDataDisposerSpy).toBeCalledWith();
});
