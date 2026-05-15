// @flow
import loadResourceLocatorInputTypeByWebspace from '../loadResourceLocatorInputTypeByWebspace';
import webspaceStore from '../../../stores/webspaceStore';

beforeEach(() => {
    webspaceStore.setWebspaces([]);
});

test.each(['sulu', 'example'])('Load input type for resource locator by webspace', (webspaceKey) => {
    webspaceStore.setWebspaces([({
        key: webspaceKey,
        resourceLocatorStrategy: {inputType: 'leaf'},
    }: any)]);

    return expect(loadResourceLocatorInputTypeByWebspace(webspaceKey)).resolves.toEqual('leaf');
});
