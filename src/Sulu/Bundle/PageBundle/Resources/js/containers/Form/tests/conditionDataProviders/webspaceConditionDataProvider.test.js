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

    const webspaces = [webspace1, webspace2];
    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({webspace: 'sulu'}, {webspace: 'test'}, {}))
        .toEqual({__webspace: webspace2, __webspaces: webspaces});
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

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({}, {webspace: 'sulu'}, {webspace: 'test'}))
        .toEqual({__webspace: webspace2, __webspaces: webspaces});
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

    const webspaces = [webspace1, webspace2];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({}, {}, {webspace: 'test'}))
        .toEqual({__webspace: webspace1, __webspaces: webspaces});
});

test('Return empty data if webspace does not exist', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };

    const webspaces = [webspace1];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({webspace: 'sulu'}, {}, {})).toEqual({__webspaces: webspaces});
});

test('Return empty data if no webspace prop exists on data', () => {
    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };

    const webspaces = [webspace1];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({}, {}, {})).toEqual({__webspaces: webspaces});
});
