/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import {observable, toJS, when} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import metadataStore from '../../stores/MetadataStore';

jest.mock('../../stores/MetadataStore', () => ({
    getSchema: jest.fn(() => Promise.resolve()),
}));

function LoadingStrategy() {
    this.load = jest.fn().mockReturnValue({then: jest.fn()});
    this.initialize = jest.fn();
    this.reset = jest.fn();
    this.destroy = jest.fn();
}

class StructureStrategy {
    @observable data = [];
    clear = jest.fn();
    getData = jest.fn().mockReturnValue(this.data);
    enhanceItem = jest.fn((item) => item);
}

test('The loading strategy should be called when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const additionalValue = observable.box(5);
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
            additionalValue,
        },
        {
            test: 'value',
        }
    );

    structureStrategy.getData.mockReturnValue([]);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        toJS(datagridStore.data),
        'tests',
        {
            additionalValue: 5,
            locale: undefined,
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different resourceKey when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: undefined,
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different page when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: undefined,
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    page.set(3);
    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: undefined,
            page: 3,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different locale when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data, 'snippets',
        {
            locale: 'en',
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    locale.set('de');
    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: 'de',
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with the defined sortings', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.sort('title', 'desc');
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            page: 1,
            sortBy: 'title',
            sortOrder: 'desc',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with the defined search', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(2);
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    structureStrategy.clear = jest.fn();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.search('search-value');

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            page: 1,
            search: 'search-value',
        },
        structureStrategy.enhanceItem
    );

    expect(structureStrategy.clear).toBeCalled();

    datagridStore.destroy();
});

test('The loading strategy should be called with the active item as parent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);
    datagridStore.setActive('some-uuid');

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            page: 1,
            parent: 'some-uuid',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The active item should not be passed as parent if undefined', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable.box(1);
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
        },
        {
            parent: 9,
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            page: 1,
            parent: 9,
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('Set loading flag to true before schema is loaded', () => {
    const promise = Promise.resolve();
    metadataStore.getSchema.mockReturnValue(promise);
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {page});
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    page.set(1);
    datagridStore.setDataLoading(false);
    expect(datagridStore.loading).toEqual(true);
    return promise.then(() => {
        expect(datagridStore.loading).toEqual(false);
        datagridStore.destroy();
    });
});

test('Set loading flag to true before request', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {page});
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    page.set(1);
    datagridStore.setDataLoading(false);
    datagridStore.sendRequest();
    expect(datagridStore.loading).toEqual(true);
    datagridStore.destroy();
});

test('Set loading flag to false after request', (done) => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {page});
    const promise = Promise.resolve({
        pages: 3,
    });
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    loadingStrategy.load.mockReturnValue(promise);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);
    datagridStore.sendRequest();
    return promise.then(() => {
        expect(datagridStore.loading).toEqual(false);
        datagridStore.destroy();
        done();
    });
});

test('Get fields from MetadataStore for correct resourceKey', () => {
    const fields = {
        test: {},
    };
    const promise = Promise.resolve(fields);
    metadataStore.getSchema.mockReturnValue(promise);

    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    expect(metadataStore.getSchema).toBeCalledWith('tests');
    return promise.then(() => {
        expect(datagridStore.schema).toBe(fields);
        datagridStore.destroy();
    });
});

test('After initialization no row should be selected', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    expect(datagridStore.selections.length).toBe(0);
    datagridStore.destroy();
});

test('Select an item', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.select({id: 1}) ;
    datagridStore.select({id: 2}) ;
    expect(toJS(datagridStore.selectionIds)).toEqual([1, 2]);

    datagridStore.deselect({id: 1});
    expect(toJS(datagridStore.selectionIds)).toEqual([2]);
    datagridStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.select({id: 1}) ;
    datagridStore.deselect({id: 2});

    expect(toJS(datagridStore.selectionIds)).toEqual([1]);
    datagridStore.destroy();
});

test('Select the entire page', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {page});
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.structureStrategy.data = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [
        {id: 1},
        {id: 7},
    ];
    datagridStore.selectEntirePage();
    expect(toJS(datagridStore.selectionIds)).toEqual([1, 7, 2, 3]);
    datagridStore.destroy();
});

test('Deselect the entire page', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.structureStrategy.data = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [
        {id: 1},
        {id: 2},
        {id: 7},
    ];
    datagridStore.deselectEntirePage();
    expect(toJS(datagridStore.selectionIds)).toEqual([7]);
    datagridStore.destroy();
});

test('Clear the selection', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.selections = [1, 4, 5];
    page.set(1);
    expect(datagridStore.selections).toHaveLength(3);

    datagridStore.clearSelection();
    expect(datagridStore.selections).toHaveLength(0);
});

test('Clear the data', () => {
    const datagridStore = new DatagridStore('tests', {
        page: observable.box(),
    });
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(new LoadingStrategy(), structureStrategy);

    datagridStore.clearData();
    expect(structureStrategy.clear).toBeCalled();
});

test('Should reset the data array and set page to 1 when the reload method is called', (done) => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    const promise = Promise.resolve({});
    loadingStrategy.load.mockReturnValue(promise);

    const page = observable.box();
    const locale = observable.box();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    locale.set('en');
    page.set(3);
    datagridStore.setActive(1);

    when(
        () => !datagridStore.loading,
        () => {
            expect(page.get()).toBe(3);

            datagridStore.reload();
            expect(structureStrategy.clear).toBeCalled();
            expect(datagridStore.active).toBe(undefined);

            expect(page.get()).toBe(1);
            expect(loadingStrategy.load).toBeCalled();

            datagridStore.destroy();
            done();
        }
    );
});

test('Should reset page count to 0 and page to 1 when locale is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    locale.set('de');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when locale stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    locale.set('en');

    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when search is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.searchTerm.set('test');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when search stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});
    datagridStore.searchTerm.set('test');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.searchTerm.set('test');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when sort column is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortColumn.set('test');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort column stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});
    datagridStore.sortColumn.set('test');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortColumn.set('test');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count to 0 and page to 1 when sort order is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortOrder.set('asc');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should not reset page count to 0 and page to 1 when sort order stays the same', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});
    datagridStore.sortOrder.set('asc');

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    datagridStore.sortOrder.set('asc');

    expect(structureStrategy.clear).not.toBeCalled();
    expect(page.get()).toEqual(2);
    expect(datagridStore.pageCount).toEqual(7);
    datagridStore.destroy();
});

test('Should reset page count and page when strategy changes', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', {page});

    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    datagridStore.setPage(5);
    datagridStore.pageCount = 7;
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
    datagridStore.destroy();
});

test('Should call all disposers if destroy is called', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box()});
    datagridStore.sendRequestDisposer = jest.fn();
    datagridStore.localeDisposer = jest.fn();
    datagridStore.searchDisposer = jest.fn();
    datagridStore.sortColumnDisposer = jest.fn();
    datagridStore.sortOrderDisposer = jest.fn();

    datagridStore.destroy();

    expect(datagridStore.sendRequestDisposer).toBeCalledWith();
    expect(datagridStore.localeDisposer).toBeCalledWith();
    expect(datagridStore.searchDisposer).toBeCalledWith();
    expect(datagridStore.sortColumnDisposer).toBeCalledWith();
    expect(datagridStore.sortOrderDisposer).toBeCalledWith();
});
