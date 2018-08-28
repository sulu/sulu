// @flow
import {observable} from 'mobx';
import SmartContentStore from '../../stores/SmartContentStore';
import ResourceRequester from '../../../../services/ResourceRequester';
import Requester from '../../../../services/Requester';

jest.mock('../../../../services/Config', () => ({
    endpoints: {
        items: '/api/items',
    },
}));

jest.mock('../../../../services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../../services/Requester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({_embedded: {}})),
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
        'content',
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

        smartContentStore.destroy();
    });
});

test('Do not load items if not FilterCriteria is given', () => {
    const locale = observable.box('en');

    new SmartContentStore('content', undefined, locale, 'pages', 4);
    expect(Requester.get).not.toBeCalled();
});

test('Load items and store them in the items variable', () => {
    const locale = observable.box('en');
    const filterCriteria = {
        dataSource: undefined,
        includeSubFolders: true,
        categories: undefined,
        categoryOperator: 'and',
        tags: ['Test1', 'Test3'],
        tagOperator: 'or',
        audienceTargeting: true,
        sortBy: 'changed',
        sortMethod: 'asc',
        presentAs: 'large',
        limitResult: 9,
    };

    const items = [
        {id: 1},
        {id: 2},
        {id: 3},
    ];
    const itemsPromise = Promise.resolve({
        _embedded: {
            items,
        },
    });
    Requester.get.mockReturnValue(itemsPromise);
    const smartContentStore = new SmartContentStore('content', filterCriteria, locale, 'pages', 4);

    expect(Requester.get).toHaveBeenLastCalledWith(
        '/api/items?provider=content&excluded=4&locale=en&audienceTargeting=true&categoryOperator=and'
        + '&includeSubFolders=true&limitResult=9&sortBy=changed&sortMethod=asc&tagOperator=or&tags=Test1,Test3'
        + '&presentAs=large'
    );

    return itemsPromise.then(() => {
        expect(smartContentStore.items).toEqual(items);

        const updatedItems = [
            {id: 1},
        ];
        const updatedItemsPromise = Promise.resolve({
            _embedded: {
                items: updatedItems,
            },
        });
        Requester.get.mockReturnValue(updatedItemsPromise);

        smartContentStore.limit = 1;

        expect(Requester.get).toHaveBeenLastCalledWith(
            '/api/items?provider=content&excluded=4&locale=en&audienceTargeting=true&categoryOperator=and'
            + '&includeSubFolders=true&limitResult=1&sortBy=changed&sortMethod=asc&tagOperator=or&tags=Test1,Test3'
            + '&presentAs=large'
        );

        return updatedItemsPromise.then(() => {
            expect(smartContentStore.items).toEqual(updatedItems);
        });
    });
});

test('Generate filterCriteria from current state', () => {
    const smartContentStore = new SmartContentStore('content');
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

    smartContentStore.destroy();
});

test('Dispose autorun on unmount', () => {
    const smartContentStore = new SmartContentStore('content');
    const itemDisposerSpy = jest.fn();

    smartContentStore.itemDisposer = itemDisposerSpy;
    smartContentStore.destroy();
    expect(itemDisposerSpy).toBeCalledWith();
});
