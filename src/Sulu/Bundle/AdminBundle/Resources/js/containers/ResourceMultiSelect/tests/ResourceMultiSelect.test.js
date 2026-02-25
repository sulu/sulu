// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceMultiSelect from '../ResourceMultiSelect';
import ResourceListStore from '../../../stores/ResourceListStore';
import loaderStyles from '../../../components/Loader/loader.scss';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

function mockResourceListStoreData(data: ?Array<Object>, loading: boolean = false) {
    // $FlowFixMe
    ResourceListStore.mockImplementation(function() {
        this.loading = loading;
        this.data = data;
    });
}

test('Render with data', () => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
        {'id': 99, 'name': 'Test XYZ', 'someOtherProperty': 'maybe maybe'},
    ]);

    const {asFragment} = render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(ResourceListStore).toHaveBeenCalledWith('test', {limit: ''}, 'id');
    expect(asFragment()).toMatchSnapshot();
});

test('Render in disabled state', () => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
    ]);

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
    expect(screen.getByRole('button', {name: /sulu_admin\.none_selected/})).toBeDisabled();
});

test('Render in loading state', () => {
    mockResourceListStoreData(undefined, true);

    render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={undefined}
        />
    );

    expect(document.querySelector(`.${loaderStyles.spinner}`)).not.toBeNull();
});

test('Pass requestParameters', () => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
        {'id': 99, 'name': 'Test XYZ', 'someOtherProperty': 'maybe maybe'},
    ]);

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
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
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

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test', {limit: ''}, 'id'],
        ['test', {limit: '', testOption: 'testValue'}, 'id'],
    ]);
});

test('Pass requestParameters when resourceKey props changed', () => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
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

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test1', {limit: ''}, 'id'],
        ['test2', {limit: ''}, 'id'],
    ]);
});

test('The component should trigger the change callback', async() => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
        {'id': 99, 'name': 'Test XYZ', 'someOtherProperty': 'maybe maybe'},
    ]);

    const onChangeSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <ResourceMultiSelect
            displayProperty="name"
            onChange={onChangeSpy}
            resourceKey="test"
            values={[99]}
        />
    );

    const expectedValues = [
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
        {'id': 99, 'name': 'Test XYZ', 'someOtherProperty': 'maybe maybe'},
    ];

    await user.click(screen.getByRole('button', {name: /Test XYZ/}));
    await user.click(screen.getByRole('button', {name: 'Test DEF'}));
    expect(onChangeSpy).toHaveBeenCalledWith([99, 5], expectedValues);
});

test('The component should trigger the close callback', async() => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
    ]);

    const closeSpy = jest.fn();
    const user = userEvent.setup();

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
    await user.click(screen.getByRole('button', {name: /sulu_admin\.none_selected/}));
    await user.keyboard('{Escape}');
    expect(closeSpy).toHaveBeenCalled();
});
