// @flow
import webspaceStore from '../../stores/webspaceStore';

export default function loadResourceLocatorInputTypeByWebspace(webspaceKey: string) {
    return webspaceStore.loadWebspace(webspaceKey)
        .then((webspace) => {
            return webspace.resourceLocatorStrategy.inputType;
        });
}
