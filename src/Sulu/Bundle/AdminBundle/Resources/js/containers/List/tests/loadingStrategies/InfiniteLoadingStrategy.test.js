// @flow
import 'url-search-params-polyfill';
import InfiniteLoadingStrategy from '../../loadingStrategies/InfiniteLoadingStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [],
        },
    })),
}));

jest.mock('../../../List/stores/metadataStore', () => ({
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
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    }));

    const result = infiniteLoadingStrategy.load(
        'snippets',
        {
            page: 1,
        },
        undefined
    );

    return result.then(() => {
        expect(structureStrategy.clear).not.toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);
    });
});

test('Should load items and add to existing entries in array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    }));

    const parentId = 17;
    const result = infiniteLoadingStrategy.load(
        'snippets',
        {
            page: 1,
            locale: 'en',
        },
        parentId
    );

    return result.then(() => {
        expect(structureStrategy.clear).not.toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, parentId);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, parentId);
    });
});

test('Should load items of previous pages and given page if previous pages are not loaded', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    // request for loading items of previous pages
    ResourceRequester.getList.mockReturnValueOnce(Promise.resolve({
        _embedded: {
            snippets: [{id: 1}, {id: 2}, {id: 3}, {id: 4}],
        },
    }));

    // request for loading items of current page
    ResourceRequester.getList.mockReturnValueOnce(Promise.resolve({
        _embedded: {
            snippets: [{id: 5}, {id: 6}],
        },
    }));

    const result = infiniteLoadingStrategy.load(
        'snippets',
        {page: 3},
        undefined
    );

    return result.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 1, limit: 100});
        expect(structureStrategy.clear).toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 3}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 4}, undefined);

        expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 3, limit: 50});
        expect(structureStrategy.addItem).toBeCalledWith({id: 5}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 6}, undefined);
    });
});

test('Should not load items of previous pages if given page is the expected next page', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    ResourceRequester.getList.mockReturnValueOnce(Promise.resolve({
        _embedded: {
            snippets: [{id: 1}, {id: 2}],
        },
    }));

    const firstPageResult = infiniteLoadingStrategy.load(
        'snippets',
        {page: 1},
        undefined
    );

    return firstPageResult.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 1, limit: 50});
        expect(structureStrategy.clear).not.toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);

        ResourceRequester.getList.mockReturnValueOnce(Promise.resolve({
            _embedded: {
                snippets: [{id: 3}, {id: 4} ],
            },
        }));

        const secondPageResult = infiniteLoadingStrategy.load(
            'snippets',
            {page: 2},
            undefined
        );

        return secondPageResult.then(() => {
            expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 2, limit: 50});
            expect(structureStrategy.clear).not.toBeCalled();
            expect(structureStrategy.addItem).toBeCalledWith({id: 3}, undefined);
            expect(structureStrategy.addItem).toBeCalledWith({id: 4}, undefined);
        });
    });
});

test('Should clear and reload items if given page is already loaded', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [{id: 1}, {id: 2}],
        },
    }));

    const firstResult = infiniteLoadingStrategy.load(
        'snippets',
        {page: 1},
        undefined
    );

    return firstResult.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 1, limit: 50});
        expect(structureStrategy.clear).not.toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);

        jest.clearAllMocks();

        const secondResult = infiniteLoadingStrategy.load(
            'snippets',
            {page: 1},
            undefined
        );

        return secondResult.then(() => {
            expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 1, limit: 50});
            expect(structureStrategy.clear).toBeCalled();
            expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
            expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);
        });
    });
});

test('Should load items with correct options', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    const result = infiniteLoadingStrategy.load(
        'snippets',
        {
            page: 1,
            locale: 'en',
        },
        undefined
    );

    return result.then(() => {
        expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 50, page: 1, locale: 'en'});
    });
});
