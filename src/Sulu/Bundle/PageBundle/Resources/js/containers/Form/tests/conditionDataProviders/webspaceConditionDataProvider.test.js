// @flow
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import defaultWebspace from 'sulu-admin-bundle/utils/TestHelper/defaultWebspace';
import webspaceStore from '../../../../stores/webspaceStore';
import webspaceConditionDataProvider from '../../conditionDataProviders/webspaceConditionDataProvider';

jest.mock(
    'sulu-admin-bundle/containers/Form/stores/ResourceFormStore',
    () => jest.fn(function(resourceStore, formKey, options, metadataOptions) {
        this.options = options;
        this.metadataOptions = metadataOptions;
    })
);

jest.mock('sulu-admin-bundle/stores/ResourceStore/ResourceStore', () => jest.fn());

test('Return webspace from data', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'), 'test', {webspace: 'test'})
    );

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

    expect(webspaceConditionDataProvider({webspace: 'sulu'}, '/test', formInspector))
        .toEqual({__webspace: webspace2, __webspaces: webspaces});
});

test('Return webspace from options', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'), 'test', {webspace: 'sulu'}, {webspace: 'test'})
    );

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

    expect(webspaceConditionDataProvider({}, '/test', formInspector))
        .toEqual({__webspace: webspace2, __webspaces: webspaces});
});

test('Return webspace from metadataOptions', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'), 'test', {}, {webspace: 'test'})
    );

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

    expect(webspaceConditionDataProvider({}, '/test', formInspector))
        .toEqual({__webspace: webspace1, __webspaces: webspaces});
});

test('Return empty data if webspace does not exist', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'), 'test', {}, {})
    );

    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };

    const webspaces = [webspace1];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({webspace: 'sulu'}, '/test', formInspector)).toEqual({__webspaces: webspaces});
});

test('Return empty data if no webspace prop exists on data', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(new ResourceStore('test'), 'test', {}, {})
    );

    const webspace1 = {
        ...defaultWebspace,
        key: 'test',
        name: 'Test',
    };

    const webspaces = [webspace1];

    webspaceStore.setWebspaces(webspaces);

    expect(webspaceConditionDataProvider({}, '/test', formInspector)).toEqual({__webspaces: webspaces});
});
