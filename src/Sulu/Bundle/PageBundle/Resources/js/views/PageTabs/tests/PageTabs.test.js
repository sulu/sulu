/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount} from 'enzyme';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import PageTabs from '../PageTabs';
import webspaceStore from '../../../stores/WebspaceStore';

jest.mock('../../../stores/WebspaceStore', () => ({
    loadWebspace: jest.fn(),
}));

jest.mock('sulu-admin-bundle/views', () => ({
    ResourceTabs: jest.fn(() => null),
}));

test('Pass locales from webspace and titleProperty to ResourceTabs component', () => {
    const webspace = {
        allLocalizations: [
            {name: 'en'},
            {name: 'de'},
        ],
    };

    const webspacePromise = Promise.resolve(webspace);

    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const route = {
        options: {},
    };

    const router = {
        attributes: {
            webspace: 'sulu',
        },
        route,
    };

    const pageTabs = mount(<PageTabs route={route} router={router}>{() => null}</PageTabs>);

    expect(pageTabs.find(ResourceTabs).prop('locales')).toEqual([]);

    return webspacePromise.then(() => {
        pageTabs.update();
        expect(pageTabs.find(ResourceTabs).prop('locales')).toEqual(['en', 'de']);
        expect(pageTabs.find(ResourceTabs).prop('titleProperty')).toEqual('title');
    });
});
