// @flow
import smartContentConfigStore from '../../stores/smartContentConfigStore';

beforeEach(() => {
    smartContentConfigStore.clear();
});

test('Set config and return config for given provider', () => {
    const mediaProviderConfig = {
        audienceTargeting: true,
        categories: true,
        datasourceResourceKey: 'collections',
        datasourceAdapter: 'table',
        tags: true,
        presentAs: true,
        sorting: [],
        limit: true,
    };

    const contentProviderConfig = {
        audienceTargeting: true,
        categories: true,
        datasourceResourceKey: 'pages',
        datasourceAdapter: 'column_list',
        tags: true,
        presentAs: true,
        sorting: [],
        limit: true,
    };

    smartContentConfigStore.setConfig({
        media: mediaProviderConfig,
        content: contentProviderConfig,
    });

    expect(smartContentConfigStore.getConfig('media')).toBe(mediaProviderConfig);
    expect(smartContentConfigStore.getConfig('content')).toBe(contentProviderConfig);
});
