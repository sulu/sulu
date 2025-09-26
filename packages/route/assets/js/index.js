// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import initializer from 'sulu-admin-bundle/services/initializer';
import {ResourceLocator} from './containers';

initializer.addUpdateConfigHook('sulu_route', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

    fieldRegistry.add(
        'route',
        ResourceLocator,
        {
            historyResourceKey: 'routes',
            resourceStorePropertiesToRequest: { // TODO: Move in future to schemaOptions and prepend via MetadataListener
                parentUuid: 'parentId',
                parentId: 'parentId',
            },
            generationUrl: config.generateUrl,
            options: {history: true},
        }
    );
});
