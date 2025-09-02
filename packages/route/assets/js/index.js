// @flow
import {Config} from 'sulu-admin-bundle/services';
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';
import initializer from 'sulu-admin-bundle/services/initializer';
import type {FieldTypeProps} from 'sulu-admin-bundle/containers/Form/types';

initializer.addUpdateConfigHook('sulu_route', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

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
            generationUrl: Config.endpoints.generateUrl,
            options: {history: true},
        }
    );
});
