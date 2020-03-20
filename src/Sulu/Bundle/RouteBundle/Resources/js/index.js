// @flow
import {resourceRouteRegistry} from 'sulu-admin-bundle/services/ResourceRequester';
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';
import initializer from 'sulu-admin-bundle/services/initializer';
import PageTreeRoute from './containers/Form/fields/PageTreeRoute';

initializer.addUpdateConfigHook('sulu_admin', () => {
    const routeGenerationUrl = resourceRouteRegistry.getListUrl('routes', {action: 'generate'});

    fieldRegistry.add(
        'route',
        ResourceLocator,
        {
            historyResourceKey: 'routes',
            modeResolver: () => {
                return Promise.resolve('full');
            },
            generationUrl: routeGenerationUrl,
            options: {history: true},
        }
    );

    fieldRegistry.add(
        'page_tree_route',
        PageTreeRoute,
        {
            modeResolver: () => {
                return Promise.resolve('leaf');
            },
        }
    );
});
