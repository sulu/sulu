// @flow
import {bundleReady} from 'sulu-admin-bundle/services';
import initializer from 'sulu-admin-bundle/services/Initializer';
import {sidebarViewRegistry} from 'sulu-admin-bundle/containers/Sidebar';
import Preview, {previewConfigStore} from './views/Preview';

initializer.addUpdateConfigHook('sulu_preview', (config: Object) => {
    previewConfigStore.setConfig(config);

    if (previewConfigStore.mode === 'off') {
        sidebarViewRegistry.disable('sulu_preview.preview');
    }
});

sidebarViewRegistry.add('sulu_preview.preview', Preview);

bundleReady();
