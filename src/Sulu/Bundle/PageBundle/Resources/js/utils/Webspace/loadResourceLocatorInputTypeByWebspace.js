// @flow
import webspaceStore from '../../stores/webspaceStore';

export default function loadResourceLocatorInputTypeByWebspace(webspaceKey: string) {
    return Promise.resolve(webspaceStore.getWebspace(webspaceKey).resourceLocatorStrategy.inputType);
}
