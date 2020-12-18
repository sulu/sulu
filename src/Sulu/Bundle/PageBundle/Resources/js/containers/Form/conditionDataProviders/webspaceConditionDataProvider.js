// @flow
import {toJS} from 'mobx';
import {FormInspector} from 'sulu-admin-bundle/containers';
import webspaceStore from '../../../stores/webspaceStore';

export default function(
    data: {[string]: any},
    dataPath: ?string,
    formInspector: FormInspector
): {[string]: any} {
    const {options, metadataOptions} = formInspector;
    const webspaceKey = data.webspace || options.webspace || (metadataOptions && metadataOptions.webspace);

    const conditionData = {};
    conditionData.__webspaces = toJS(webspaceStore.allWebspaces);
    if (webspaceKey && webspaceStore.hasWebspace(webspaceKey)) {
        conditionData.__webspace = webspaceStore.getWebspace(webspaceKey);
    }

    return conditionData;
}
