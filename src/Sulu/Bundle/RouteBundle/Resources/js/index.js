// @flow
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';
import PageTreeRoute from './containers/Form/fields/PageTreeRoute';

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

fieldRegistry.add(
    'page_tree_route',
    PageTreeRoute,
    {
        historyResourceKey: 'routes',
        modeResolver: () => {
            return Promise.resolve('leaf');
        },
        options: {history: true},
    }
);
