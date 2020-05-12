// @flow
import defaultWebspace from 'sulu-admin-bundle/utils/TestHelper/defaultWebspace';
import webspaceStore from '../../../../stores/webspaceStore';
import webspaceConditionDataProvider from '../../conditionDataProviders/webspaceConditionDataProvider';

test('Return webspace from data', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };
    const webspace2 = {
        ...defaultWebspace,
        key: 'sulu',
        name: 'Sulu',
    };
    webspaceStore.setWebspaces([webspace1, webspace2]);

    expect(webspaceConditionDataProvider({webspace: 'sulu'}, {webspace: 'test'}, {})).toEqual({__webspace: webspace2});
});

test('Return webspace from options', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };
    const webspace2 = {
        ...defaultWebspace,
        key: 'sulu',
        name: 'Sulu',
    };
    webspaceStore.setWebspaces([webspace1, webspace2]);

    expect(webspaceConditionDataProvider({}, {webspace: 'sulu'}, {webspace: 'test'})).toEqual({__webspace: webspace2});
});

test('Return webspace from metadataOptions', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };
    const webspace2 = {
        ...defaultWebspace,
        key: 'sulu',
        name: 'Sulu',
    };
    webspaceStore.setWebspaces([webspace1, webspace2]);

    expect(webspaceConditionDataProvider({}, {}, {webspace: 'test'})).toEqual({__webspace: webspace1});
});

test('Return empty data if webspace does not exist', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };
    webspaceStore.setWebspaces([webspace1]);

    expect(webspaceConditionDataProvider({webspace: 'sulu'}, {}, {})).toEqual({});
});

test('Return empty data if no webspace prop exists on data', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };
    webspaceStore.setWebspaces([webspace1]);

    expect(webspaceConditionDataProvider({}, {}, {})).toEqual({});
});
