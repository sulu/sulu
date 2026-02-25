// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import SearchContainer from '../../../containers/Search';

jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));
jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));
jest.mock('../../../containers/Search', () => jest.fn(() => null));

test('Render search component', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const Search = require('../Search').default;

    const router = new Router({});
    render(<Search route={router.route} router={router} />);

    expect(getLatestMockProps(SearchContainer).router).toEqual(router);

    const toolbarFunction = findWithHighOrderFunction(withToolbar, Search);
    expect(toolbarFunction()).toEqual({});
});
