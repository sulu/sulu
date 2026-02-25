// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceCheckboxGroup from '../ResourceCheckboxGroup';
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
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
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
    expect(screen.getByDisplayValue('2')).toBeDisabled();
    expect(screen.getByDisplayValue('5')).toBeDisabled();
});

test('Render in loading state', () => {
    mockResourceListStoreData(undefined, true);

    render(
        <ResourceCheckboxGroup
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
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
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

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test', requestParameters1],
        ['test', requestParameters2],
    ]);
});

test('Pass requestParameters when resourceKey props changed', () => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
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

    // $FlowFixMe
    expect(ResourceListStore.mock.calls).toEqual([
        ['test1', requestParameters],
        ['test2', requestParameters],
    ]);
});

test('Render with values', () => {
    mockResourceListStoreData([
        {'id': 2, 'name': 'Test ABC', 'someOtherProperty': 'No no'},
        {'id': 5, 'name': 'Test DEF', 'someOtherProperty': 'YES YES'},
        {'id': 99, 'name': 'Test XYZ', 'someOtherProperty': 'maybe maybe'},
    ]);

    render(
        <ResourceCheckboxGroup
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            values={[5, 99]}
        />
    );

    expect(screen.getByDisplayValue('2')).not.toBeChecked();
    expect(screen.getByDisplayValue('5')).toBeChecked();
    expect(screen.getByDisplayValue('99')).toBeChecked();
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
        <ResourceCheckboxGroup
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

    await user.click(screen.getByDisplayValue('5'));
    expect(onChangeSpy).toHaveBeenCalledWith([99, 5], expectedValues);
});
