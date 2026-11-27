/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from '@testing-library/react';
import {ResourceTabs} from 'sulu-admin-bundle/views';
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

    render(<PageTabs route={route} router={router}>{() => null}</PageTabs>);

    expect(webspaceStore.getWebspace).toHaveBeenCalledWith('sulu');
    expect(ResourceTabs.mock.calls[0][0].locales).toEqual(['en', 'de']);
    expect(ResourceTabs.mock.calls[0][0].titleProperty).toEqual('title');
});

test('Pass the titleProperty of the route to ResourceTabs component', () => {
    webspaceStore.getWebspace.mockReturnValue({allLocalizations: []});

    const route = {
        options: {titleProperty: ''},
    };

    const router = {
        attributes: {
            webspace: 'sulu',
        },
        route,
    };

    render(<PageTabs route={route} router={router}>{() => null}</PageTabs>);

    expect(ResourceTabs).toHaveBeenLastCalledWith(expect.objectContaining({titleProperty: ''}), expect.anything());
});

test('Derive the id from the webspace if the idFromWebspace option is set', () => {
    expect(PageTabs.getDerivedRouteAttributes({options: {idFromWebspace: true}}, {webspace: 'blog'}))
        .toEqual({id: 'blog'});
});

test('Derive no id if the idFromWebspace option is not set', () => {
    expect(PageTabs.getDerivedRouteAttributes({options: {}}, {webspace: 'blog'})).toEqual({});
});

test('Set the id to the selected webspace, even if the router still has the one of another', () => {
    webspaceStore.getWebspace.mockReturnValue({allLocalizations: []});

    const route = {options: {idFromWebspace: true}};
    const router = {
        attributes: {
            id: 'sulu',
            webspace: 'blog',
        },
        route,
    };

    render(<PageTabs route={route} router={router}>{() => null}</PageTabs>);

    expect(router.attributes.id).toEqual('blog');
});

test('Keep the id of the router if the idFromWebspace option is not set', () => {
    webspaceStore.getWebspace.mockReturnValue({allLocalizations: []});

    const route = {options: {}};
    const router = {
        attributes: {
            id: '123-456',
            webspace: 'blog',
        },
        route,
    };

    render(<PageTabs route={route} router={router}>{() => null}</PageTabs>);

    expect(router.attributes.id).toEqual('123-456');
});
