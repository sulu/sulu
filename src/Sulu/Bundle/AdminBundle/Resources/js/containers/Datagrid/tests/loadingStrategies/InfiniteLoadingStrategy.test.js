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

jest.mock('../../../Datagrid/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve()),
}));

class StructureStrategy {
    addItem = jest.fn();
    clear = jest.fn();
    data = [];
    findById = jest.fn();
    remove = jest.fn();
    visibleItems = [];
}

test('Should load items and add to empty array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    const promise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(promise);
    infiniteLoadingStrategy.load(
        'snippets',
        {
            page: 2,
        },
        undefined
    );

    return promise.then(() => {
        expect(structureStrategy.clear).not.toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, undefined);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, undefined);
    });
});

test('Should load items and add to existing entries in array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    const promise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(promise);
    const parent = 17;
    infiniteLoadingStrategy.load(
        'snippets',
        {
            page: 1,
            locale: 'en',
        },
        parent
    );

    return promise.then(() => {
        expect(structureStrategy.clear).not.toBeCalled();
        expect(structureStrategy.addItem).toBeCalledWith({id: 1}, parent);
        expect(structureStrategy.addItem).toBeCalledWith({id: 2}, parent);
    });
});

test('Should load items with correct options', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const structureStrategy = new StructureStrategy();
    infiniteLoadingStrategy.setStructureStrategy(structureStrategy);

    infiniteLoadingStrategy.load(
        'snippets',
        {
            page: 2,
            locale: 'en',
        },
        undefined
    );

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 50, page: 2, locale: 'en'});
});
