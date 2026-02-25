/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from '@testing-library/react';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import PageTabs from '../PageTabs';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
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

    webspaceStore.getWebspace.mockReturnValue(webspace);

    const route = {
        options: {},
    };

    const router = {
        attributes: {
            webspace: 'sulu',
        },
        route,
    };

    render(
        <PageTabs route={route} router={router}>
            {() => null}
        </PageTabs>
    );

    expect(webspaceStore.getWebspace).toBeCalledWith('sulu');
    const resourceTabsProps = getLatestMockProps(ResourceTabs);
    expect(resourceTabsProps.locales).toEqual(['en', 'de']);
    expect(resourceTabsProps.titleProperty).toEqual('title');
});
