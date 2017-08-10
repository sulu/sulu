/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Toolbar from '../Toolbar';
import toolbarStore from '../stores/ToolbarStore';

jest.mock('../stores/ToolbarStore', () => ({
    hasBackButtonConfig() {
        return false;
    },

    getBackButtonConfig() {
        return this.config.backButton || null;
    },

    hasItemsConfig() {
        return false;
    },

    getItemsConfig() {
        return this.config.items || [];
    },

    hasIconsConfig() {
        return false;
    },

    getIconsConfig() {
        return this.config.icons || [];
    },

    hasLocaleConfig() {
        return false;
    },

    getLocaleConfig() {
        return this.config.locale;
    },
}));

test('Render the items from the ToolbarStore', () => {
    toolbarStore.hasItemsConfig = jest.fn();
    toolbarStore.hasItemsConfig.mockReturnValue(true);

    toolbarStore.config = {
        items: [
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
    };

    const view = render(<Toolbar />);
    expect(view).toMatchSnapshot();
});
