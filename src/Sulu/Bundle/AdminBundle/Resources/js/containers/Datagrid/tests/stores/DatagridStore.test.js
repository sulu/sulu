/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import {observable, toJS, when} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import metadataStore from '../../stores/MetadataStore';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

jest.mock('../../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
}));

test('Do not send request without defined page parameter', () => {
    const page = observable();
    new DatagridStore('tests', {
        page,
    });
    expect(ResourceRequester.getList).not.toBeCalled();
});

test('Send request with default parameters', (done) => {
    const Promise = require.requireActual('promise');
    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        pages: 3,
        _embedded: {
            tests: [{id: 1}],
        },
    }));
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    page.set(1);
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1});
    when(
        () => !datagridStore.loading,
        () => {
            expect(toJS(datagridStore.data)).toEqual([{id: 1}]);
            expect(datagridStore.pageCount).toEqual(3);
            datagridStore.destroy();
            done();
        }
    );
});

test('Send request to other base URL', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    page.set(1);
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1});
    datagridStore.destroy();
});

test('Send request to other page', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    page.set(1);
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1});
    page.set(2);
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 2});
    datagridStore.destroy();
});

test('Send request to other locale', () => {
    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
        locale,
    });
    datagridStore.init('pagination');
    page.set(1);
    locale.set('en');
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1, locale: 'en'});
    locale.set('de');
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1, locale: 'de'});
    datagridStore.destroy();
});

test('Send not request without locale if undefined', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    page.set(1);
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1});
    expect(ResourceRequester.getList.mock.calls[0][1]).not.toHaveProperty('locale');
    expect(ResourceRequester.getList).toBeCalledWith('tests', {page: 1});
    datagridStore.destroy();
});

test('Set loading flag to true before request', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    page.set(1);
    datagridStore.setLoading(false);
    datagridStore.sendRequest();
    expect(datagridStore.loading).toEqual(true);
    datagridStore.destroy();
});

test('Set loading flag to false after request', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    const Promise = require.requireActual('promise');
    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [],
        },
    }));
    datagridStore.init('pagination');
    datagridStore.sendRequest();
    when(
        () => !datagridStore.loading,
        () => {
            expect(datagridStore.loading).toEqual(false);
            datagridStore.destroy();
        }
    );
});

test('Get fields from MetadataStore for correct resourceKey', () => {
    const fields = {
        test: {},
    };
    metadataStore.getSchema.mockReturnValue(fields);

    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    expect(datagridStore.getSchema()).toBe(fields);
    expect(metadataStore.getSchema).toBeCalledWith('tests');
    datagridStore.destroy();
});

test('After initialization no row should be selected', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    expect(datagridStore.selections.length).toBe(0);
    datagridStore.destroy();
});

test('Select an item', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    datagridStore.select(1);
    datagridStore.select(2);
    expect(toJS(datagridStore.selections)).toEqual([1, 2]);

    datagridStore.deselect(1);
    expect(toJS(datagridStore.selections)).toEqual([2]);
    datagridStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    datagridStore.select(1);
    datagridStore.deselect(2);

    expect(toJS(datagridStore.selections)).toEqual([1]);
    datagridStore.destroy();
});

test('Select the entire page', (done) => {
    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    datagridStore.selections = [1, 7];
    page.set(1);
    when(
        () => !datagridStore.loading,
        () => {
            datagridStore.selectEntirePage();
            expect(toJS(datagridStore.selections)).toEqual([1, 7, 2, 3]);
            datagridStore.destroy();
            done();
        }
    );
});

test('Deselect the entire page', (done) => {
    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    datagridStore.selections = [1, 2, 7];
    page.set(1);
    when(
        () => !datagridStore.loading,
        () => {
            datagridStore.deselectEntirePage();
            expect(toJS(datagridStore.selections)).toEqual([7]);
            datagridStore.destroy();
            done();
        }
    );
});

test('Clear the selection', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.init('pagination');
    datagridStore.selections = [1, 4, 5];
    page.set(1);
    expect(datagridStore.selections).toHaveLength(3);

    datagridStore.clearSelection();
    expect(datagridStore.selections).toHaveLength(0);
});

test('The data should be appended when the loading strategy is infiniteScroll', () => {
    const loadingStrategy = 'infiniteScroll';
    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );

    datagridStore.init(loadingStrategy);

    datagridStore.handleResponse({
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }, loadingStrategy);

    expect(toJS(datagridStore.data)).toEqual([
        {id: 1},
        {id: 2},
        {id: 3},
    ]);

    datagridStore.handleResponse({
        _embedded: {
            tests: [
                {id: 4},
                {id: 5},
                {id: 6},
            ],
        },
    }, loadingStrategy);

    expect(toJS(datagridStore.data)).toEqual([
        {id: 1},
        {id: 2},
        {id: 3},
        {id: 4},
        {id: 5},
        {id: 6},
    ]);

    datagridStore.destroy();
});

test('When loading strategy is infiniteScroll, changing the locale resets the data property and sets page to 1', () => {
    ResourceRequester.getList.mockReturnValue(
        Promise.resolve({
            _embedded: {
                tests: [
                    {id: 1},
                    {id: 2},
                    {id: 3},
                ],
            },
        })
    );

    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.init('infiniteScroll');

    page.set(3);
    locale.set('en');

    expect(page.get()).toBe(1);
    expect(toJS(datagridStore.data)).toEqual([]);

    datagridStore.destroy();
});

test('When loading strategy was changed to pagination, changing the locale should not reset page to 1', () => {
    ResourceRequester.getList.mockReturnValue(Promise.resolve({
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    }));

    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.init('infiniteScroll');
    datagridStore.updateLoadingStrategy('pagination');

    page.set(3);
    locale.set('en');

    expect(page.get()).toBe(3);
    expect(toJS(datagridStore.data)).toEqual([]);

    datagridStore.destroy();
});

test('When loading strategy was changed to pagination, data and pageCount should be reset', () => {
    const promise = Promise.resolve({
        pages: 5,
        _embedded: {
            tests: [
                {id: 1},
                {id: 2},
                {id: 3},
            ],
        },
    });
    ResourceRequester.getList.mockReturnValue(promise);

    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.init('infiniteScroll');

    return promise.then(() => {
        expect(datagridStore.pageCount).toBe(5);
        datagridStore.updateLoadingStrategy('pagination');

        expect(datagridStore.pageCount).toBe(0);
        datagridStore.destroy();
    });
});

test('Should reset the data array and set page to 1 when the reload method is called', () => {
    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.init('infiniteScroll');
    datagridStore.updateLoadingStrategy('pagination');

    page.set(3);
    locale.set('en');

    expect(page.get()).toBe(3);

    datagridStore.reload();

    expect(page.get()).toBe(1);
    expect(datagridStore.data.toJS()).toEqual([]);
    expect(ResourceRequester.getList).toBeCalled();

    datagridStore.destroy();
});
