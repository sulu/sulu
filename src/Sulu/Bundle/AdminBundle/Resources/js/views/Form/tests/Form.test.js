/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';

jest.mock('../../../containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('../../../services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.delete':
                return 'Delete';
        }
    },
}));

beforeEach(() => {
    jest.resetModules();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
            },
        },
    };
    const form = mount(<Form router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    toolbarConfig.backButton.onClick();
    expect(router.navigate).toBeCalledWith('test_route');
});

test('Should not render back button when no editLink is configured', () => {
    const withToolbar = require('../../../containers/Toolbar/withToolbar');
    const Form = require('../Form').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        navigate: jest.fn(),
        route: {
            options: {},
        },
    };
    const form = mount(<Form router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(form);
    expect(toolbarConfig.backButton).toBe(undefined);
});
