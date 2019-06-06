// @flow
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';

fieldRegistry.add(
    'route',
    ResourceLocator,
    {
        defaultMode: 'full',
        historyResourceKey: 'routes',
        options: {history: true},
    }
);
