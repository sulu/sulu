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
    load = jest.fn();
    destroy = jest.fn();
    initialize = jest.fn();
    reset = jest.fn();
}

class StructureStrategy {
    data: Array<Object>;

    clear = jest.fn();
    getData = jest.fn();
    findById = jest.fn();
    enhanceItem = jest.fn();
}

class TestAdapter extends AbstractAdapter {
    static LoadingStrategy = LoadingStrategy;

    static StructureStrategy = StructureStrategy;

    static icon = 'su-view';

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
