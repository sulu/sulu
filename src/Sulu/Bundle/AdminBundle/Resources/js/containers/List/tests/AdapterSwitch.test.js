// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import AdapterSwitch from '../AdapterSwitch';
import listAdapterRegistry from '../registries/listAdapterRegistry';

jest.mock('../registries/listAdapterRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(),
    has: jest.fn(),
}));

class TestAdapter {
    static icon = 'su-th-large';
}

beforeEach(() => {
    jest.clearAllMocks();
    listAdapterRegistry.has.mockReturnValue(true);
    listAdapterRegistry.get.mockReturnValue(TestAdapter);
});

function renderAdapterSwitch(currentAdapter = 'table', onAdapterChange = jest.fn()) {
    return render(
        <AdapterSwitch
            adapters={['table', 'folder']}
            currentAdapter={currentAdapter}
            onAdapterChange={onAdapterChange}
        />
    );
}

function getAdapterButtons() {
    return screen.getAllByLabelText('su-th-large').map((icon) => icon.closest('button'));
}

test('The component should render with current adapter "folder"', () => {
    renderAdapterSwitch('folder');

    const buttons = getAdapterButtons();

    expect(buttons[0]).not.toHaveClass('active');
    expect(buttons[1]).toHaveClass('active');
});

test('The component should render with current adapter "table"', () => {
    renderAdapterSwitch('table');

    const buttons = getAdapterButtons();

    expect(buttons[0]).toHaveClass('active');
    expect(buttons[1]).not.toHaveClass('active');
});

test('The component should handle adapter change correctly', async() => {
    const user = userEvent.setup();
    const handleAdapterChange = jest.fn();
    renderAdapterSwitch('table', handleAdapterChange);

    const buttons = getAdapterButtons();

    await user.click(buttons[0]);
    expect(handleAdapterChange).not.toHaveBeenCalled();

    await user.click(buttons[1]);
    expect(handleAdapterChange).toHaveBeenCalledWith('folder');
});
