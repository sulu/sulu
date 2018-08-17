// @flow
import React from 'react';
import {mount} from 'enzyme';
import Router from 'sulu-admin-bundle/services/Router';
import {findWithToolbarFunction} from 'sulu-admin-bundle/utils/TestHelper';
import WebspaceStore from '../../../stores/WebspaceStore';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
    Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
    DatagridStore: jest.fn(function() {
        this.activeItems = [];
        this.active = {
            get: jest.fn(),
            set: jest.fn(),
        };
        this.sortColumn = {
            get: jest.fn(),
        };
        this.sortOrder = {
            get: jest.fn(),
        };
        this.selections = [];
        this.selectionIds = [];
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
}));

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => ({
    get: jest.fn().mockReturnValue(require('sulu-admin-bundle/containers/Datagrid/adapters/ColumnListAdapter').default),
    has: jest.fn().mockReturnValue(true),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    userStore: {
        setPersistentSetting: jest.fn(),
        getPersistentSetting: jest.fn(),
    },
}));

jest.mock('../../../stores/WebspaceStore', () => ({
    loadWebspaces: jest.fn(() => Promise.resolve()),
}));

jest.mock('sulu-admin-bundle/services/Router', () => jest.fn(function() {
    this.bind = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

beforeEach(() => {
    jest.resetModules();
});

test('Render WebspaceOverview', () => {
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');
    const promise = Promise.resolve(
        [
            {key: 'sulu', localizations: [{locale: 'en', default: true}]},
            {key: 'sulu_blog', localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}]},
        ]
    );
    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);
    webspaceOverview.instance().datagridStore.data = [
        [
            {id: 1},
        ],
    ];

    return promise.then(() => {
        webspaceOverview.update();
        expect(webspaceOverview.render()).toMatchSnapshot();
    });
});

test('Should change webspace when value of webspace select is changed', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const WebspaceOverview = require('../WebspaceOverview').default;
    // $FlowFixMe
    const toolbarFunction = findWithToolbarFunction(withToolbar, WebspaceOverview);
    // $FlowFixMe
    const webspaceStore: typeof WebspaceStore = require('../../../stores/WebspaceStore');
    const userStore = require('sulu-admin-bundle/stores').userStore;

    const promise = Promise.resolve(
        [
            {
                key: 'sulu',
                localizations: [{locale: 'en', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
            {
                key: 'sulu_blog',
                localizations: [{locale: 'en', default: false}, {locale: 'de', default: true}],
                allLocalizations: [{localization: 'en', name: 'en'}, {localization: 'de', name: 'de'}],
            },
        ]
    );

    webspaceStore.loadWebspaces.mockReturnValue(promise);

    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);

    return promise.then(() => {
        webspaceOverview.update();
        webspaceOverview.instance().webspace.set('sulu');
        webspaceOverview.instance().locale.set('en');
        expect(webspaceOverview.instance().webspace.get()).toBe('sulu');
        expect(webspaceOverview.instance().locale.get()).toBe('en');

        const toolbarConfig = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfig.locale.value).toBe('en');
        expect(toolbarConfig.locale.options).toEqual(
            expect.arrayContaining(
                [
                    expect.objectContaining({label: 'en', value: 'en'}),
                    expect.objectContaining({label: 'de', value: 'de'}),
                ]
            )
        );

        webspaceOverview.update();
        webspaceOverview.find('WebspaceSelect').prop('onChange')('sulu_blog');
        expect(webspaceOverview.instance().datagridStore.destroy).toBeCalledWith();
        expect(webspaceOverview.instance().datagridStore.active.set).toBeCalledWith(undefined);
        expect(webspaceOverview.instance().webspace.get()).toBe('sulu_blog');
        expect(webspaceOverview.instance().locale.get()).toBe('de');
        expect(userStore.setPersistentSetting).lastCalledWith('sulu_content.webspace_overview.webspace', 'sulu_blog');

        const toolbarConfigNew = toolbarFunction.call(webspaceOverview.instance());
        expect(toolbarConfigNew.locale.value).toBe('de');
        expect(toolbarConfigNew.locale.options).toEqual(
            expect.arrayContaining(
                [
                    expect.objectContaining({label: 'de', value: 'de'}),
                ]
            )
        );
    });
});

test('Should load webspace and active route attribute from userStore', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const userStore = require('sulu-admin-bundle/stores').userStore;

    userStore.getPersistentSetting.mockImplementation((key) => {
        if (key === 'sulu_content.webspace_overview.webspace') {
            return 'sulu';
        }

        if (key === 'sulu_content.webspace_overview.webspace.sulu.active') {
            return 'some-uuid';
        }
    });

    // $FlowFixMe
    expect(WebspaceOverview.getDerivedRouteAttributes()).toEqual({
        active: 'some-uuid',
        webspace: 'sulu',
    });
});

test('Should bind router', () => {
    const WebspaceOverview = require('../WebspaceOverview').default;
    const router = new Router({});

    const webspaceOverview = mount(<WebspaceOverview route={router.route} router={router} />);
    webspaceOverview.instance().webspace.set('sulu');
    const page = webspaceOverview.instance().page;
    const locale = webspaceOverview.instance().locale;
    const webspace = webspaceOverview.instance().webspace;

    expect(router.bind).toBeCalledWith('page', page, '1');
    expect(router.bind).toBeCalledWith('locale', locale);
    expect(router.bind).toBeCalledWith('webspace', webspace);
    expect(router.bind).toBeCalledWith('active', webspaceOverview.instance().datagridStore.active);
});
