// @flow
import {observable} from 'mobx';
import SmartContentStore from '../../stores/SmartContentStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    get: jest.fn(),
}));

test('Load categories and datasource when constructed', () => {
    const locale = observable.box('en');

    const dataSource = {id: 4};
    const dataSourcePromise = Promise.resolve(dataSource);
    const categories = [
        {id: 1},
        {id: 2},
        {id: 4},
    ];
    const categoriesPromise = Promise.resolve({
        _embedded: {
            categories,
        },
    });
    ResourceRequester.get.mockImplementation((resourceKey) => {
        switch(resourceKey) {
            case 'pages':
                return dataSourcePromise;
            case 'categories':
                return categoriesPromise;
        }
    });

    const smartContentStore = new SmartContentStore(
        {
            audienceTargeting: undefined,
            categories: [1, 2, 4],
            categoryOperator: undefined,
            dataSource: 4,
            includeSubFolders: undefined,
            limitResult: undefined,
            sortBy: undefined,
            sortMethod: undefined,
            tagOperator: undefined,
            tags: undefined,
            presentAs: undefined,
        },
        locale,
        'pages'
    );

    expect(smartContentStore.loading).toEqual(true);
    expect(ResourceRequester.get).toBeCalledWith('pages', 4, {locale: 'en'});
    expect(ResourceRequester.get).toBeCalledWith('categories', undefined, {ids: [1, 2, 4], locale: 'en'});

    return Promise.all([dataSourcePromise, categoriesPromise]).then(() => {
        expect(smartContentStore.loading).toEqual(false);
        expect(smartContentStore.dataSource).toEqual(dataSource);
        expect(smartContentStore.categories).toEqual(categories);
    });
});

test('Generate filterCriteria from current state', () => {
    const smartContentStore = new SmartContentStore();
    smartContentStore.dataSource = {id: 6};
    smartContentStore.includeSubElements = true;
    smartContentStore.categories = [{id: 4}, {id: 8}];
    smartContentStore.categoryOperator = 'and';
    smartContentStore.tags = ['Test1', 'Test3'];
    smartContentStore.tagOperator = 'or';
    smartContentStore.audienceTargeting = true;
    smartContentStore.sortBy = 'changed';
    smartContentStore.sortOrder = 'asc';
    smartContentStore.presentation = 'large';
    smartContentStore.limit = 9;

    expect(smartContentStore.filterCriteria).toEqual({
        audienceTargeting: true,
        categories: [4, 8],
        categoryOperator: 'and',
        dataSource: 6,
        includeSubFolders: true,
        limitResult: 9,
        presentAs: 'large',
        sortBy: 'changed',
        sortMethod: 'asc',
        tagOperator: 'or',
        tags: ['Test1', 'Test3'],
    });
});
