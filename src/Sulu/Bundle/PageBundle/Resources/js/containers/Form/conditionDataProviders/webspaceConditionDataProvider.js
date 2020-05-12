// @flow
import webspaceStore from '../../../stores/webspaceStore';

export default function(
    data: {[string]: any},
    options: {[string]: any},
    metadataOptions: ?{[string]: any}
): {[string]: any} {
    const webspaceKey = data.webspace || options.webspace || (metadataOptions && metadataOptions.webspace);
    if (!webspaceKey || !webspaceStore.hasWebspace(webspaceKey)) {
        return {};
    }

    return {__webspace: webspaceStore.getWebspace(webspaceKey)};
}
