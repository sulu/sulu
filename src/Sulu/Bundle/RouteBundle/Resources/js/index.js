// @flow
import {resourceRouteRegistry} from 'sulu-admin-bundle/services/ResourceRequester';
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';
import initializer from 'sulu-admin-bundle/services/initializer';

initializer.addUpdateConfigHook('sulu_admin', () => {
    const generationUrl = resourceRouteRegistry.getListUrl('routes', {action: 'generate'});

    fieldRegistry.add(
        'route',
        ResourceLocator,
        {
            historyResourceKey: 'routes',
            modeResolver: () => {
                return Promise.resolve('full');
            },
            generationUrl: generationUrl,
            options: {history: true},
        }
    );
});
