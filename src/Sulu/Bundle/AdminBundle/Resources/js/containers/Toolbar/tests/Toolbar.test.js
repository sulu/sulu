/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Toolbar from '../Toolbar';
import toolbarStorePool from '../stores/ToolbarStorePool';

let toolbarStoreMock = {};

jest.mock('../stores/ToolbarStorePool', () => ({
    createStore: jest.fn(),
    getStore: jest.fn(),
}));

beforeEach(() => {
    toolbarStoreMock = {
        hasBackButtonConfig: jest.fn(),

        getBackButtonConfig: jest.fn(),

        hasItemsConfig: jest.fn(),

        getItemsConfig: jest.fn(),

        hasIconsConfig: jest.fn(),

        getIconsConfig: jest.fn(),

        hasLocaleConfig: jest.fn(),

        getLocaleConfig: jest.fn(),
    };
});

test('Render the items from the ToolbarStore', () => {
    const storeKey = 'testStore';

    toolbarStorePool.createStore.mockReturnValue(toolbarStoreMock);

    toolbarStoreMock.hasItemsConfig.mockReturnValue(true);
    toolbarStoreMock.hasIconsConfig.mockReturnValue(false);
    toolbarStoreMock.hasLocaleConfig.mockReturnValue(false);
    toolbarStoreMock.hasBackButtonConfig.mockReturnValue(false);

    toolbarStoreMock.getItemsConfig.mockReturnValue(
        [
            {
                type: 'button',
                label: 'Delete',
                disabled: true,
                icon: 'trash-o',
            },
            {
                type: 'dropdown',
                label: 'Save',
                icon: 'floppy-more',
                options: [
                    {
                        label: 'Save as draft',
                        onClick: () => {},
                    },
                    {
                        label: 'Publish',
                        onClick: () => {},
                    },
                    {
                        label: 'Save and publish',
                        onClick: () => {},
                    },
                ],
            },
        ],
    );

    const view = render(<Toolbar storeKey={storeKey} />);
    expect(view).toMatchSnapshot();
    expect(toolbarStorePool.createStore).toBeCalledWith(storeKey);
});
