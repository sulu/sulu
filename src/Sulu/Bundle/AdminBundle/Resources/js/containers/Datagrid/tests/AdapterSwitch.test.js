// @flow
import {mount} from 'enzyme';
import React from 'react';
import AdapterSwitch from '../AdapterSwitch';
import AbstractAdapter from '../adapters/AbstractAdapter';
import datagridAdapterRegistry from '../registries/DatagridAdapterRegistry';

jest.mock('../registries/DatagridAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

class LoadingStrategy {
    destroy = jest.fn();
    initialize = jest.fn();
    load = jest.fn();
    reset = jest.fn();
    setStructureStrategy = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;
    visibleItems: Array<Object>;

    addItem = jest.fn();
    clear = jest.fn();
    findById = jest.fn();
    order = jest.fn();
    remove = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    static icon = 'su-th-large';

    render() {
        return (
            <div>Test Adapter</div>
        );
    }
}

beforeEach(() => {
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.get.mockReturnValue(TestAdapter);
});

test('The component should render with current adapter "folder"', () => {
    const adapters = ['table', 'folder'];
    const currentAdapterKey = 'folder';
    const handleAdapterChange = jest.fn();
    const view = mount(
        <AdapterSwitch
            adapters={adapters}
            currentAdapter={currentAdapterKey}
            onAdapterChange={handleAdapterChange}
        />
    ).render();

    expect(view).toMatchSnapshot();
});

test('The component should render with current adapter "table"', () => {
    const adapters = ['table', 'folder'];
    const currentAdapterKey = 'table';
    const handleAdapterChange = jest.fn();
    const view = mount(
        <AdapterSwitch
            adapters={adapters}
            currentAdapter={currentAdapterKey}
            onAdapterChange={handleAdapterChange}
        />
    ).render();

    expect(view).toMatchSnapshot();
});

test('The component should handle adapter change correctly', () => {
    const adapters = ['table', 'folder'];
    const currentAdapterKey = 'table';
    const handleAdapterChange = jest.fn();
    const view = mount(
        <AdapterSwitch
            adapters={adapters}
            currentAdapter={currentAdapterKey}
            onAdapterChange={handleAdapterChange}
        />
    );

    // click on the active adapter shouldn't trigger the event
    view.find('Button').at(0).simulate('click');
    expect(handleAdapterChange).not.toBeCalled();

    // click on not active should trigger the event correctly
    view.find('Button').at(1).simulate('click');
    expect(handleAdapterChange).toBeCalledWith('folder');
});
