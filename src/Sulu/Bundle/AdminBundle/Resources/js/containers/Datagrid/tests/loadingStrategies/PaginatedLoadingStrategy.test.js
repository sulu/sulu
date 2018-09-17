// @flow
import 'url-search-params-polyfill';
import PaginatedLoadingStrategy from '../../loadingStrategies/PaginatedLoadingStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [],
        },
    })),
}));

jest.mock('../../../Datagrid/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve()),
}));

class StructureStrategy {
    addItem = jest.fn();
    clear = jest.fn();
    data = [];
    findById = jest.fn();
    remove = jest.fn();
    order = jest.fn();
    visibleItems = [];
}

test('Should load items and add to empty array', () => {
    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    paginatedLoadingStrategy.setStructureStrategy(structureStrategy);

    const promise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(promise);
    paginatedLoadingStrategy.load(
        'snippets',
        {
            page: 2,
        },
        undefined
    );

    return promise.then(() => {
        expect(structureStrategy.clear).toBeCalledWith(undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);
    });
});

test('Should load items and replace existing entries in array', () => {
    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    paginatedLoadingStrategy.setStructureStrategy(structureStrategy);

    const promise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(promise);
    const parentId = 15;
    paginatedLoadingStrategy.load(
        'snippets',
        {
            page: 1,
            locale: 'en',
        },
        parentId
    );

    return promise.then(() => {
        expect(structureStrategy.clear).toBeCalledWith(parentId);
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, parentId);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, parentId);
    });
});

test('Should load items with correct options', () => {
    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    paginatedLoadingStrategy.setStructureStrategy(structureStrategy);

    paginatedLoadingStrategy.load(
        'snippets',
        {
            page: 2,
            limit: 10,
            locale: 'en',
        },
        undefined
    );

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 10, page: 2, locale: 'en'});
});
