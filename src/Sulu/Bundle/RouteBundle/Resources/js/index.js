// @flow
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';

fieldRegistry.add(
    'route',
    ResourceLocator,
    {
        historyResourceKey: 'routes',
        modeResolver: () => {
            return Promise.resolve('full');
        },
        options: {history: true},
    }
);
