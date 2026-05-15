// @flow
import {observable} from 'mobx';
import Requester from '../Requester';

beforeEach(() => {
    window.fetch = jest.fn();
});

function mockFetchResponse(response) {
    window.fetch.mockReturnValue(Promise.resolve(response));
}

test('Should execute GET request and reject with response when the response contains error', () => {
    const response = {
        ok: false,
        statusText: 'An error occured!',
    };

    mockFetchResponse(response);

    expect(Requester.get('/some-url')).rejects.toEqual(response);

    expect(window.fetch).toBeCalledWith('/some-url', {
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        method: 'GET',
        signal: expect.any(AbortSignal),
    });
});

test('Should execute GET request and reject if array is returned', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve([
        {
            test1: undefined,
            test2: null,
            test3: '',
            test4: 'something',
        },
    ]));

    mockFetchResponse(response);

    return expect(Requester.get('/some-url')).rejects.toThrow('array');
});

test('Should execute GET request and replace null with undefined', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({
        test1: undefined,
        test2: null,
        test3: '',
        test4: 'something',
        test5: {
            test5_id: 5,
            test5_test: null,
        },
        test6: [
            {id: 1, test: 'abc', test2: null},
            {id: 2, test: 'abc', test2: 'Test2'},
        ],
        test7: ['test1', 'test2'],
    }));

    mockFetchResponse(response);

    const requestPromise = Requester.get('/some-url').then((data) => {
        expect(data).toEqual({
            test1: undefined,
            test2: undefined,
            test3: '',
            test4: 'something',
            test5: {
                test5_id: 5,
                test5_test: undefined,
            },
            test6: [
                {id: 1, test: 'abc', test2: undefined},
                {id: 2, test: 'abc', test2: 'Test2'},
            ],
            test7: ['test1', 'test2'],
        });
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        method: 'GET',
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute POST request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));

    mockFetchResponse(response);

    const data = {
        title: 'Titel',
        description: 'Description',
        test: undefined,
        contacts: [
            {id: 1, test: 'Titel', other: undefined},
            {id: 2, test: 'Titel', other: 'Other'},
        ],
        address: {
            id: 1,
            title: 'Title',
            other: 'Other',
            other2: undefined,
        },
        types: ['type1', 'type2'],
        observableTest: observable({property1: 'test', property2: 'test2'}),
        observableArrayTest: observable(['oa1', 'oa2']),
    };
    const requestPromise = Requester.post('/some-url', data).then((response) => {
        expect(response).toEqual({test: '', value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'POST',
        body: JSON.stringify({
            title: 'Titel',
            description: 'Description',
            test: null,
            contacts: [
                {id: 1, test: 'Titel', other: null},
                {id: 2, test: 'Titel', other: 'Other'},
            ],
            address: {
                id: 1,
                title: 'Title',
                other: 'Other',
                other2: null,
            },
            types: ['type1', 'type2'],
            observableTest: {
                property1: 'test',
                property2: 'test2',
            },
            observableArrayTest: ['oa1', 'oa2'],
        }),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute POST request and return JSON when value is observable', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));

    mockFetchResponse(response);

    const data = observable({id: 'test', name: 'Cool object'});
    const requestPromise = Requester.post('/some-url', data).then((response) => {
        expect(response).toEqual({test: '', value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'POST',
        body: JSON.stringify({
            id: 'test',
            name: 'Cool object',
        }),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute PUT request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));

    mockFetchResponse(response);

    const data = {
        title: 'Titel',
        description: 'Description',
        test: undefined,
    };
    const requestPromise = Requester.put('/some-url', data).then((response) => {
        expect(response).toEqual({test: '', value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'PUT',
        body: JSON.stringify({
            title: 'Titel',
            description: 'Description',
            test: null,
        }),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute PUT request without data and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));

    mockFetchResponse(response);

    const requestPromise = Requester.put('/some-url', null).then((response) => {
        expect(response).toEqual({test: '', value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'PUT',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute PATCH request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve([{test: '', value: 'test'}]));

    mockFetchResponse(response);

    const data = [
        {
            title: 'Titel',
            description: 'Description',
            test: undefined,
        },
    ];

    const requestPromise = Requester.patch('/some-url', data).then((response) => {
        expect(response).toEqual([{test: '', value: 'test'}]);
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'PATCH',
        body: JSON.stringify([{title: 'Titel', description: 'Description', test: null}]),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute DELETE request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));

    mockFetchResponse(response);

    const requestPromise = Requester.delete('/some-url').then((data) => {
        expect(data).toEqual({test: '', value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        signal: expect.any(AbortSignal),
    });

    return requestPromise;
});

test('Should execute DELETE request and return empty object if status code was 204', () => {
    mockFetchResponse({
        ok: true,
        status: 204,
    });

    return Requester.delete('/some-url').then((data) => {
        expect(data).toEqual({});
    });
});

test('Shold call response hooks', () => {
    const handleResponseHook = jest.fn();
    Requester.handleResponseHooks.push(handleResponseHook);

    mockFetchResponse({
        ok: true,
        status: 204,
    });

    return Requester.get('/some-url').then(() => {
        expect(handleResponseHook).toHaveBeenCalledTimes(1);
        Requester.handleResponseHooks = [];
    });
});
