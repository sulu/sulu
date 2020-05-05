// @flow
import webspaceStore from '../../../stores/webspaceStore';

export default function(data: {[string]: any}): {[string]: any} {
    if (!data.webspace || !webspaceStore.hasWebspace(data.webspace)) {
        return {};
    }

    return {__webspace: webspaceStore.getWebspace(data.webspace)};
}
