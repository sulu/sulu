// @flow
import {resourceRouteRegistry} from 'sulu-admin-bundle/services/ResourceRequester';
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';
import initializer from 'sulu-admin-bundle/services/initializer';
import type {FieldTypeProps} from 'sulu-admin-bundle/containers/Form/types';

initializer.addUpdateConfigHook('sulu_admin', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

    const routeGenerationUrl = resourceRouteRegistry.getUrl('list', 'routes', {action: 'generate'});

    fieldRegistry.add(
        'route',
        ResourceLocator,
        {
            historyResourceKey: 'routes',
            modeResolver: (props: FieldTypeProps<?string>) => {
                const {
                    schemaOptions: {
                        mode: {
                            value: mode = 'full',
                        } = {},
                    },
                } = props;

                return Promise.resolve(mode);
            },
            generationUrl: routeGenerationUrl,
            options: {history: true},
        }
    );
});
