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
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
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
        types: [
            {name: 'default', value: 'default'},
            {name: 'homepage', value: 'homepage'},
        ],
    };

    smartContentConfigStore.setConfig({
        media: mediaProviderConfig,
        content: contentProviderConfig,
    });

    expect(smartContentConfigStore.getConfig('media')).toBe(mediaProviderConfig);
    expect(smartContentConfigStore.getConfig('content')).toBe(contentProviderConfig);
});

test('Return default value for given provider with presentations', () => {
    const mediaProviderConfig = {
        audienceTargeting: false,
        categories: false,
        datasourceResourceKey: 'collections',
        datasourceAdapter: 'table',
        tags: false,
        presentAs: false,
        sorting: [],
        limit: false,
        types: [],
    };

    const pagesProviderConfig = {
        audienceTargeting: true,
        categories: true,
        datasourceResourceKey: 'pages',
        datasourceAdapter: 'column_list',
        tags: true,
        presentAs: true,
        sorting: [{name: 'title', value: 'Title'}],
        limit: true,
        types: [{name: 'default', value: 'default'}],
    };

    smartContentConfigStore.setConfig({
        media: mediaProviderConfig,
        pages: pagesProviderConfig,
    });

    expect(smartContentConfigStore.getDefaultValue('pages', [{name: 'two', value: 'Two columns'}]))
        .toEqual({
            audienceTargeting: false,
            categories: undefined,
            categoryOperator: 'or',
            dataSource: undefined,
            includeSubFolders: false,
            limitResult: undefined,
            presentAs: 'two',
            sortBy: 'title',
            sortMethod: 'asc',
            tagOperator: 'or',
            tags: undefined,
            types: ['default'],
        });

    expect(smartContentConfigStore.getDefaultValue('media', []))
        .toEqual({
            audienceTargeting: undefined,
            categories: undefined,
            categoryOperator: undefined,
            dataSource: undefined,
            includeSubFolders: false,
            limitResult: undefined,
            presentAs: undefined,
            sortBy: undefined,
            sortMethod: undefined,
            tagOperator: undefined,
            tags: undefined,
            types: undefined,
        });
});
