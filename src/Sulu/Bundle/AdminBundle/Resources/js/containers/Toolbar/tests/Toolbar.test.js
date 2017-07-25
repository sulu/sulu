/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import Toolbar from '../Toolbar';
import {render} from 'enzyme';
import toolbarStore from '../stores/ToolbarStore';

jest.mock('../stores/ToolbarStore', () => ({}));

test('Render the items from the ToolbarStore', () => {
    toolbarStore.items = [
        {
            title: 'Save',
            enabled: false,
            icon: 'save',
        },
        {
            title: 'Delete',
            icon: 'delete',
        },
    ];
    const view = render(<Toolbar />);
    expect(view).toMatchSnapshot();
});
