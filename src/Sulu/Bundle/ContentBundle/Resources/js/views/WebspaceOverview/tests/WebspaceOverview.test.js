// @flow
import React from 'react';
import {mount, render} from 'enzyme';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        withToolbar: jest.fn((Component) => Component),
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function() {
            this.getPage = jest.fn().mockReturnValue(1);
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.updateStrategies = jest.fn();
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        FullLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/FullLoadingStrategy'
        ).default,
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(require('sulu-admin-bundle/containers/Datagrid/adapters/ColumnListAdapter').default),
    has: jest.fn().mockReturnValue(true),
}));

test('Render WebspaceOverview', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = {
        bind: jest.fn(),
        attributes: {},
    };

    const webspaceOverview = render(<WebspaceOverview router={router} />);
    expect(webspaceOverview).toMatchSnapshot();
});

test('Should change webspace when value of select is changed', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = {
        bind: jest.fn(),
        attributes: {},
    };

    const webspaceOverview = mount(<WebspaceOverview router={router} />);

    webspaceOverview.instance().webspace.set('sulu');
    expect(webspaceOverview.instance().webspace.get()).toBe('sulu');

    webspaceOverview.find('WebspaceSelect').prop('onChange')('sulu_blog');
    expect(webspaceOverview.instance().webspace.get()).toBe('sulu_blog');
});

test('Should bind/unbind router', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        attributes: {},
    };

    const webspaceOverview = mount(<WebspaceOverview router={router} />);
    webspaceOverview.instance().webspace.set('sulu');
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];
    const webspace = router.bind.mock.calls[2][1];

    expect(router.bind).toBeCalledWith('page', page, '1');
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('webspace', webspace);

    webspaceOverview.unmount();
    expect(router.unbind).toBeCalledWith('page', page);
    expect(router.unbind).toBeCalledWith('locale', locale);
    expect(router.unbind).toBeCalledWith('webspace', webspace);
});
