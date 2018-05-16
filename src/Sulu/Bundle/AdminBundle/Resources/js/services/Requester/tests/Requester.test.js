/* eslint-disable flowtype/require-valid-file-annotation */
import Requester from '../Requester';

test('Should execute GET request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const requestPromise = Requester.get('/some-url').then((data) => {
        expect(data).toBe('test');
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
    });

    return requestPromise;
});

test('Should execute GET request and throw error if response contains error', () => {
    const response = {
        ok: false,
        statusText: 'An error occured!',
    };
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    expect(Requester.get('/some-url')).rejects.toEqual(new Error('An error occured!'));

    expect(window.fetch).toBeCalledWith('/some-url', {
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
    });
});

test('Should execute POST request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const data = {
        title: 'Titel',
        description: 'Description',
    };
    const requestPromise = Requester.post('/some-url', data).then((response) => {
        expect(response).toBe('test');
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'POST',
        body: JSON.stringify(data),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
    });

    return requestPromise;
});

test('Should execute PUT request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const data = {
        title: 'Titel',
        description: 'Description',
    };
    const requestPromise = Requester.put('/some-url', data).then((response) => {
        expect(response).toBe('test');
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'PUT',
        body: JSON.stringify(data),
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
    });

    return requestPromise;
});

test('Should execute DELETE request and return JSON', () => {
    const response = {
        json: jest.fn(),
        ok: true,
    };
    response.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(response));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const requestPromise = Requester.delete('/some-url').then((data) => {
        expect(data).toBe('test');
    });

    expect(window.fetch).toBeCalledWith('/some-url', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
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
