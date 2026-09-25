// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Router} from 'sulu-admin-bundle/services';
import SearchContainer from '../../../containers/Search';

jest.mock('sulu-admin-bundle/containers/Toolbar/stores/toolbarStorePool', () => ({
    __esModule: true,
    DEFAULT_STORE_KEY: 'default',
    default: {
        setToolbarConfig: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.bind = jest.fn();
    this.addUpdateRouteHook = jest.fn().mockReturnValue(jest.fn());
}));

jest.mock('../../../containers/Search', () => jest.fn(() => null));

test('Render search component', () => {
    const toolbarStorePool = require(
        'sulu-admin-bundle/containers/Toolbar/stores/toolbarStorePool'
    ).default;
    const Search = require('../Search').default;

    const router = new Router({});
    render(<Search route={router.route} router={router} />);

    expect(SearchContainer).toHaveBeenLastCalledWith(expect.objectContaining({router}), {});
    expect(toolbarStorePool.setToolbarConfig).toHaveBeenCalledWith('default', {});
});
