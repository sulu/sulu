/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';

jest.mock('sulu-admin-bundle/services/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.add':
                return 'Add';
            case 'sulu_admin.delete':
                return 'Delete';
        }
    },
}));

jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
    has: jest.fn().mockReturnValue(function() {
        return false;
    }),
}));

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
            },
        },
        attributes: {},
    };
    const mediaOverview = mount(<MediaOverview router={router} />).get(0);
    mediaOverview.collectionId = 4;
    mediaOverview.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});
