// @flow
import {fieldRegistry} from 'sulu-admin-bundle/containers';
import initializer from 'sulu-admin-bundle/services/initializer';
import {ResourceLocator} from './containers';
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
            generationUrl: config.generateUrl,
            options: {history: true},
        }
    );
});
