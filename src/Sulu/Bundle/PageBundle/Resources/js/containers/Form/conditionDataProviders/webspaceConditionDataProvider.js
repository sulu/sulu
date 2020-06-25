// @flow
import {toJS} from 'mobx';
import webspaceStore from '../../../stores/webspaceStore';

export default function(
    data: {[string]: any},
    options: {[string]: any},
    metadataOptions: ?{[string]: any}
): {[string]: any} {
    const webspaceKey = data.webspace || options.webspace || (metadataOptions && metadataOptions.webspace);

    const conditionData = {};
    conditionData.__webspaces = toJS(webspaceStore.allWebspaces);
    if (webspaceKey && webspaceStore.hasWebspace(webspaceKey)) {
        conditionData.__webspace = webspaceStore.getWebspace(webspaceKey);
    }

    return conditionData;
}
