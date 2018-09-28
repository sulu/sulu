// @flow
import {bundleReady, initializer} from 'sulu-admin-bundle/services';
import {sidebarRegistry} from 'sulu-admin-bundle/containers';
import Preview, {PreviewStore} from './containers';

initializer.addUpdateConfigHook('sulu_preview', (config: Object) => {
    PreviewStore.routes = config.routes;
    Preview.debounceDelay = config.debounceDelay;
    Preview.mode = config.mode;

    if (config.mode === 'off') {
        sidebarRegistry.disable('sulu_preview.preview');
    }
});

sidebarRegistry.add('sulu_preview.preview', Preview);

bundleReady();
