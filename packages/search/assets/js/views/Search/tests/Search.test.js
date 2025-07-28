// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {Router} from 'sulu-admin-bundle/services';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';
import SearchContainer from '../../../containers/Search';

jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => Component));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));

test('Render search component', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const Search = require('../Search').default;

    const router = new Router({});
    const search = shallow(<Search route={router.route} router={router} />);
    const toolbarFunction = findWithHighOrderFunction(withToolbar, Search);

    expect(search.find(SearchContainer)).toHaveLength(1);
    expect(search.find(SearchContainer).prop('router')).toEqual(router);
    expect(toolbarFunction()).toEqual({});
});
