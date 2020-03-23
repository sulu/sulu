// @flow
import loadResourceLocatorInputTypeByWebspace from '../loadResourceLocatorInputTypeByWebspace';
import webspaceStore from '../../../stores/webspaceStore';

jest.mock('../../../stores/webspaceStore', () => ({
    getWebspace: jest.fn(),
}));

test.each(['sulu', 'example'])('Load input type for resource locator by webspace', (webspaceKey) => {
    webspaceStore.getWebspace.mockReturnValue({resourceLocatorStrategy: {inputType: 'leaf'}});
    const inputType = loadResourceLocatorInputTypeByWebspace(webspaceKey);

    expect(inputType).toEqual(inputType);
    expect(webspaceStore.getWebspace).toBeCalledWith(webspaceKey);
});
