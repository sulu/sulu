/* eslint-disable flowtype/require-valid-file-annotation */
/* global global */
import Requester from '../Requester';

test('Add credentials to fetch request', () => {
    global.fetch = jest.fn();

    Requester.get('/some-url');

    expect(global.fetch).toBeCalledWith('/some-url', {credentials: 'same-origin'});
});

test('Return value from fetch call', () => {
    global.fetch = jest.fn();
    global.fetch.mockReturnValue('test');

    expect(Requester.get('/some-url')).toBe('test');
});
