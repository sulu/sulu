/* eslint-disable flowtype/require-valid-file-annotation */
/* global global */
import Requester from '../Requester';

test('Add credentials to fetch request', () => {
    const promise = new Promise(() => 'test');

    global.fetch = jest.fn();
    global.fetch.mockReturnValue(promise);

    Requester.get('/some-url');

    expect(global.fetch).toBeCalledWith('/some-url', {credentials: 'same-origin'});
});

test('Return json from fetch call', () => {
    const request = {
        json: jest.fn(),
    };
    request.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(request));

    global.fetch = jest.fn();
    global.fetch.mockReturnValue(promise);

    return Requester.get('/some-url').then(data => {
        expect(data).toBe('test');
    });
});
