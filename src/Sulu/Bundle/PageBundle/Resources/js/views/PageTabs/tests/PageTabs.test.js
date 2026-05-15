/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from '@testing-library/react';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import PageTabs from '../PageTabs';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('sulu-admin-bundle/views', () => ({
    ResourceTabs: jest.fn(() => null),
}));

beforeEach(() => {
    webspaceStore.setWebspaces([]);
});

test('Pass locales from webspace and titleProperty to ResourceTabs component', () => {
    const webspace = {
        allLocalizations: [
            {name: 'en'},
            {name: 'de'},
        ],
    };

    webspaceStore.setWebspaces([{
        key: 'sulu',
        ...webspace,
    }]);

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

    const resourceTabsProps = ResourceTabs.mock.calls[ResourceTabs.mock.calls.length - 1][0];
    expect(resourceTabsProps.locales).toEqual(['en', 'de']);
    expect(resourceTabsProps.titleProperty).toEqual('title');
});
