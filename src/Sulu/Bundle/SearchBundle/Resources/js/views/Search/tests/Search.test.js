// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Router} from 'sulu-admin-bundle/services';
import SearchContainer from '../../../containers/Search';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));

jest.mock('../../../containers/Search', () => jest.fn(() => null));

const SearchContainerMock: any = SearchContainer;

test('Render search component', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const Search = require('../Search').default;

    const router = new Router({});
    render(<Search route={router.route} router={router} />);

    const searchContainerProps = SearchContainerMock.mock.calls[SearchContainerMock.mock.calls.length - 1][0];
    expect(searchContainerProps.router).toEqual(router);
    expect((withToolbar: any).mock.calls[0][1]()).toEqual({});
});
