// @flow
import {act, render} from '@testing-library/react';
import React from 'react';
import Button from '../../../components/Button';
import AdapterSwitch from '../AdapterSwitch';
import AbstractAdapter from '../adapters/AbstractAdapter';
import listAdapterRegistry from '../registries/listAdapterRegistry';

jest.mock('../registries/listAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

jest.mock('../../../components/ButtonGroup', () => jest.fn(({children}) => <div>{children}</div>));
jest.mock('../../../components/Button', () => jest.fn(() => null));

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
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);
    jest.clearAllMocks();
});

function getButtonProps(index: number) {
    const calls = (Button: any).mock.calls;
    return calls[index][0];
}

test('The component should render with current adapter "folder"', () => {
    const adapters = ['table', 'folder'];
    const currentAdapterKey = 'folder';
    const handleAdapterChange = jest.fn();
    render(
        <AdapterSwitch
            adapters={adapters}
            currentAdapter={currentAdapterKey}
            onAdapterChange={handleAdapterChange}
        />
    );

    expect((Button: any).mock.calls).toHaveLength(2);
    expect(getButtonProps(0).active).toBe(false);
    expect(getButtonProps(1).active).toBe(true);
});

test('The component should render with current adapter "table"', () => {
    const adapters = ['table', 'folder'];
    const currentAdapterKey = 'table';
    const handleAdapterChange = jest.fn();
    render(
        <AdapterSwitch
            adapters={adapters}
            currentAdapter={currentAdapterKey}
            onAdapterChange={handleAdapterChange}
        />
    );

    expect((Button: any).mock.calls).toHaveLength(2);
    expect(getButtonProps(0).active).toBe(true);
    expect(getButtonProps(1).active).toBe(false);
});

test('The component should handle adapter change correctly', () => {
    const adapters = ['table', 'folder'];
    const currentAdapterKey = 'table';
    const handleAdapterChange = jest.fn();
    render(
        <AdapterSwitch
            adapters={adapters}
            currentAdapter={currentAdapterKey}
            onAdapterChange={handleAdapterChange}
        />
    );

    // click on the active adapter shouldn't trigger the event
    act(() => {
        const firstButtonProps = getButtonProps(0);
        firstButtonProps.onClick(firstButtonProps.value);
    });
    expect(handleAdapterChange).not.toBeCalled();

    // click on not active should trigger the event correctly
    act(() => {
        const secondButtonProps = getButtonProps(1);
        secondButtonProps.onClick(secondButtonProps.value);
    });
    expect(handleAdapterChange).toBeCalledWith('folder');
});
