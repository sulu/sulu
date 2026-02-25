// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AdapterSwitch from '../AdapterSwitch';
import AbstractAdapter from '../adapters/AbstractAdapter';
import listAdapterRegistry from '../registries/listAdapterRegistry';

jest.mock('../registries/listAdapterRegistry', () => ({
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
    jest.clearAllMocks();
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);
});

test('The component should render with current adapter "folder"', () => {
    render(
        <AdapterSwitch
            adapters={['table', 'folder']}
            currentAdapter="folder"
            onAdapterChange={jest.fn()}
        />
    );

    const buttons = screen.getAllByRole('button');

    expect(buttons).toHaveLength(2);
    expect(buttons[0]).not.toHaveClass('active');
    expect(buttons[1]).toHaveClass('active');
    expect(screen.getAllByLabelText('su-th-large')).toHaveLength(2);
});

test('The component should render with current adapter "table"', () => {
    render(
        <AdapterSwitch
            adapters={['table', 'folder']}
            currentAdapter="table"
            onAdapterChange={jest.fn()}
        />
    );

    const buttons = screen.getAllByRole('button');

    expect(buttons).toHaveLength(2);
    expect(buttons[0]).toHaveClass('active');
    expect(buttons[1]).not.toHaveClass('active');
});

test('The component should handle adapter change correctly', async() => {
    const user = userEvent.setup();
    const handleAdapterChange = jest.fn();
    render(
        <AdapterSwitch
            adapters={['table', 'folder']}
            currentAdapter="table"
            onAdapterChange={handleAdapterChange}
        />
    );

    const [tableButton, folderButton] = screen.getAllByRole('button');

    await user.click(tableButton);
    expect(handleAdapterChange).not.toHaveBeenCalled();

    await user.click(folderButton);
    expect(handleAdapterChange).toHaveBeenCalledWith('folder');
});
