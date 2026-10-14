/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from '@testing-library/react';
import {ResourceTabs} from 'sulu-admin-bundle/views';
import WebspaceSettingTabs from '../WebspaceSettingTabs';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

jest.mock('sulu-admin-bundle/views', () => ({
    ResourceTabs: jest.fn(() => null),
}));

test('Pass locales from webspace and no titleProperty to ResourceTabs component', () => {
    webspaceStore.getWebspace.mockReturnValue({
        allLocalizations: [
            {name: 'en'},
            {name: 'de'},
        ],
    });

    const route = {
        options: {},
    };

    const router = {
        attributes: {
            webspace: 'sulu',
        },
        route,
    };

    render(<WebspaceSettingTabs route={route} router={router}>{() => null}</WebspaceSettingTabs>);

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu');
    expect(ResourceTabs).toHaveBeenCalledWith(
        expect.objectContaining({locales: ['en', 'de'], titleProperty: ''}),
        expect.anything()
    );
});

test('Derive the id of the settings from the webspace', () => {
    expect(WebspaceSettingTabs.getDerivedRouteAttributes(undefined, {webspace: 'blog'})).toEqual({id: 'blog'});
});

test('Set the id of the settings to the selected webspace, even if the router still has the one of another', () => {
    webspaceStore.getWebspace.mockReturnValue({allLocalizations: []});

    const route = {options: {}};
    const router = {
        attributes: {
            id: 'sulu',
            webspace: 'blog',
        },
        route,
    };

    render(<WebspaceSettingTabs route={route} router={router}>{() => null}</WebspaceSettingTabs>);

    expect(router.attributes.id).toEqual('blog');
});
