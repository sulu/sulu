// @flow
import {mount} from 'enzyme';
import React from 'react';
import AdapterSwitch from '../AdapterSwitch';

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
