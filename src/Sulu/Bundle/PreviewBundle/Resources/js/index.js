// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import initializer from 'sulu-admin-bundle/services/Initializer';
import {sidebarViewRegistry} from 'sulu-admin-bundle/containers/Sidebar';
import Preview, {PreviewStore} from './views/Preview';

initializer.addUpdateConfigHook('sulu_preview', (config: Object) => {
    PreviewStore.routes = config.routes;
    Preview.debounceDelay = config.debounceDelay;
    Preview.mode = config.mode;

    if (config.mode === 'off') {
        sidebarViewRegistry.disable('sulu_preview.preview');
    }
});

sidebarViewRegistry.add('sulu_preview.preview', Preview);

bundleReady();
