// @flow
import {listItemActionRegistry} from 'sulu-admin-bundle/views';
import {initializer} from 'sulu-admin-bundle/services';
import RestoreItemAction from './views/List/itemActions/RestoreItemAction';

listItemActionRegistry.add('sulu_trash.restore', RestoreItemAction);

initializer.addUpdateConfigHook('sulu_trash', (config: Object) => {
    if (!config) {
        // config is undefined if SuluTrashBundle is not registered
        return;
    }

    RestoreItemAction.restoreConfigurationMapping = config.restoreConfigurationMapping;
});
