// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceCheckboxGroup from '../ResourceCheckboxGroup';
import ResourceListStore from '../../../stores/ResourceListStore';
import getMockCallArg from '../../../utils/TestHelper/getMockCallArg';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../stores/ResourceListStore', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../components/Loader', () => {
    const React = require('react');

    return jest.fn(function LoaderMock() {
        return React.createElement('div', {'data-testid': 'loader'});
    });
});

jest.mock('../../../components/Checkbox', () => {
    const React = require('react');

    const Checkbox = jest.fn(function CheckboxMock({children}) {
        return React.createElement('div', {'data-testid': 'checkbox'}, children);
    });

    const CheckboxGroup = jest.fn(function CheckboxGroupMock({children, onChange, values}) {
        const clonedChildren = React.Children.map(children, (child, index) => React.cloneElement(
            child,
            {
                ...child.props,
                checked: values.includes(child.props.value),
                key: index,
            }
        ));

        function handleChangeClick() {
            onChange([5, 99]);
        }

        return React.createElement(
            'div',
            {'data-testid': 'checkbox-group'},
            React.createElement('button', {onClick: handleChangeClick, type: 'button'}, 'change-values'),
            clonedChildren
        );
    });

    return {
        __esModule: true,
        default: Checkbox,
        CheckboxGroup,
    };
});

const checkboxModule = ((jest.requireMock('../../../components/Checkbox'): any): {
    CheckboxGroup: {mock: {calls: Array<[Object]>}, ...},
    default: {mock: {calls: Array<[Object]>}, ...},
    ...
});
const checkboxMock = checkboxModule.default;

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
    expect(getLatestMockProps(checkboxModule.CheckboxGroup).disabled).toEqual(true);
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

    expect(screen.getByTestId('loader')).toBeInTheDocument();
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

    expect(getMockCallArg(checkboxMock, 0, 0).checked).toEqual(false);
    expect(getMockCallArg(checkboxMock, 1, 0).checked).toEqual(true);
    expect(getMockCallArg(checkboxMock, 2, 0).checked).toEqual(true);
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

    await user.click(screen.getByRole('button', {name: 'change-values'}));
    expect(onChangeSpy).toHaveBeenCalledWith([5, 99], expectedValues);
});
