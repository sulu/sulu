// @flow
import Requester from '../Requester';

test('Should execute GET request and reject with response when the response contains error', () => {
    const response = {
        ok: false,
        statusText: 'An error occured!',
    };
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    expect(Requester.get('/some-url')).rejects.toEqual(response);

    expect(window.fetch).toBeCalledWith('/some-url', {
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    });
});

test('Should execute GET request and replace null and empty string with undefined', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({
        test1: undefined,
        test2: null,
        test3: '',
        test4: 'something',
    }));
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const requestPromise = Requester.get('/some-url').then((data) => {
        expect(data).toEqual({
            test1: undefined,
            test2: undefined,
            test3: undefined,
            test4: 'something',
        });
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    });

    return requestPromise;
});

test('Should execute POST request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const data = {
        title: 'Titel',
        description: 'Description',
        test: undefined,
    };
    const requestPromise = Requester.post('/some-url', data).then((response) => {
        expect(response).toEqual({test: undefined, value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'POST',
        body: JSON.stringify({
            title: 'Titel',
            description: 'Description',
            test: null,
        }),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    });

    return requestPromise;
});

test('Should execute PUT request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const data = {
        title: 'Titel',
        description: 'Description',
        test: undefined,
    };
    const requestPromise = Requester.put('/some-url', data).then((response) => {
        expect(response).toEqual({test: undefined, value: 'test'});
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
    });

    return requestPromise;
});

test('Should execute DELETE request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue(Promise.resolve({test: '', value: 'test'}));
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const requestPromise = Requester.delete('/some-url').then((data) => {
        expect(data).toEqual({test: undefined, value: 'test'});
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    });

    return requestPromise;
});

test('Should execute DELETE request and return empty object if status code was 204', () => {
    const promise = Promise.resolve({
        ok: true,
        status: 204,
    });

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    return Requester.delete('/some-url').then((data) => {
        expect(data).toEqual({});
    });
});
