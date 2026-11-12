// @flow
import {fieldRegistry, ResourceLocator} from 'sulu-admin-bundle/containers';
import {resourceRouteRegistry} from 'sulu-admin-bundle/services/ResourceRequester';
import initializer from 'sulu-admin-bundle/services/initializer';
import PageTreeRoute from '../containers/Form/fields/PageTreeRoute';

jest.mock('sulu-admin-bundle/containers', () => ({
    fieldRegistry: {
        add: jest.fn(),
    },
    ResourceLocator: jest.fn(),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    resourceRouteRegistry: {
        getUrl: jest.fn(),
    },
}));

jest.mock('sulu-admin-bundle/services/initializer', () => ({
    addUpdateConfigHook: jest.fn(),
}));

jest.mock('../containers/Form/fields/PageTreeRoute', () => jest.fn());

test('Register the route and page_tree_route field types with the url to generate routes', () => {
    (resourceRouteRegistry.getUrl: any).mockImplementation((type, resourceKey, {action, resolveConflicts}) => {
        return '/admin/api/routes?action=' + action
            + (resolveConflicts === undefined ? '' : '&resolveConflicts=' + String(resolveConflicts));
    });
    require('../index');

    const [configKey, updateConfigHook] = (initializer.addUpdateConfigHook: any).mock.calls[0];
    expect(configKey).toEqual('sulu_admin');

    updateConfigHook({}, false);

    expect(resourceRouteRegistry.getUrl).toHaveBeenCalledWith('list', 'routes', {action: 'generate'});
    expect(resourceRouteRegistry.getUrl)
        .toHaveBeenCalledWith('list', 'routes', {action: 'generate', resolveConflicts: false});
    expect(fieldRegistry.add).toHaveBeenCalledWith(
        'route',
        ResourceLocator,
        expect.objectContaining({generationUrl: '/admin/api/routes?action=generate'})
    );
    expect(fieldRegistry.add).toHaveBeenCalledWith(
        'page_tree_route',
        PageTreeRoute,
        expect.objectContaining({generationUrl: '/admin/api/routes?action=generate&resolveConflicts=false'})
    );
});
