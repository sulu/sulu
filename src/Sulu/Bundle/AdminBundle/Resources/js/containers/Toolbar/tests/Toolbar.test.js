/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import Toolbar from '../Toolbar';
import toolbarStore from '../stores/ToolbarStore';

jest.mock('../stores/ToolbarStore', () => ({}));

test('Render the items from the ToolbarStore', () => {
    toolbarStore.setConfig({
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
    });

    const view = render(<Toolbar />);
    expect(view).toMatchSnapshot();
});
