// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import {Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));
jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));
jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));
jest.mock('../../../containers/Search/stores/searchResourceStore', () => ({
    loadSearchResources: jest.fn(),
}));
jest.mock('../../../containers/Search/stores/searchStore', () => ({
    loading: false,
    page: 1,
    pages: 1,
    limit: 10,
    query: undefined,
    resourceKey: undefined,
    result: [],
    search: jest.fn(),
    setLimit: jest.fn(),
    setPage: jest.fn(),
}));

const searchResourceStore = ((jest.requireMock('../../../containers/Search/stores/searchResourceStore'): any): {
    loadSearchResources: {mock: {calls: Array<Array<any>>}, ...},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render search component', async() => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const Search = require('../Search').default;

    const router = new Router({});
    const loadSearchResourcesMock: any = searchResourceStore.loadSearchResources;
    loadSearchResourcesMock.mockResolvedValue({
        page: {
            name: 'Page',
            resourceKey: 'page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    });

    render(<Search route={router.route} router={router} />);

    await waitFor(() => expect(searchResourceStore.loadSearchResources).toHaveBeenCalledTimes(1));
    expect(screen.getByRole('textbox')).toBeInTheDocument();

    const toolbarFunction = findWithHighOrderFunction(withToolbar, Search);
    expect(toolbarFunction()).toEqual({});
});
