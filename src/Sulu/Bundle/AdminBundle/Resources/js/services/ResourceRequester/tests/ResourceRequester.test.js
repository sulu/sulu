// @flow
import 'url-search-params-polyfill';
import ResourceRequester from '../ResourceRequester';
import Requester from '../../Requester/Requester';

jest.mock('../../Requester/Requester', () => ({
    get: jest.fn(),
    put: jest.fn(),
    post: jest.fn(),
    delete: jest.fn(),
}));

jest.mock('../../../stores/ResourceMetadataStore', () => ({
    getEndpoint: jest.fn().mockImplementation((resourceKey) => {
        switch (resourceKey) {
            case 'snippets':
                return '/snippets';
            case 'contacts':
                return '/contacts';
        }
    }),
}));

test('Should send a get request and return the promise', () => {
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.get('snippets', 5);
    expect(Requester.get).toBeCalledWith('/snippets/5');
    expect(result).toBe(promise);
});

test('Should send a get request without an ID and return the promise', () => {
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.get('snippets');
    expect(Requester.get).toBeCalledWith('/snippets');
    expect(result).toBe(promise);
});

test('Should send a get request with passed options as query parameters', () => {
    const options = {locale: 'en', action: 'publish'};
    ResourceRequester.get('snippets', 5, options);
    expect(Requester.get).toBeCalledWith('/snippets/5?locale=en&action=publish');
});

test('Should send a list get request and return the promise', () => {
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.getList('snippets', {page: 1, limit: 10});
    expect(result).toBe(promise);
});

test('Should send a list get request to the correct URL with page and limit parameters', () => {
    ResourceRequester.getList('snippets', {
        page: 3,
        limit: 20,
    });
    expect(Requester.get).toBeCalledWith('/snippets?page=3&limit=20&flat=true');

    ResourceRequester.getList('snippets', {
        page: 5,
        limit: 10,
    });
    expect(Requester.get).toBeCalledWith('/snippets?page=5&limit=10&flat=true');

    ResourceRequester.getList('snippets', {
        page: 1,
        limit: 5,
    });
    expect(Requester.get).toBeCalledWith('/snippets?page=1&limit=5&flat=true');
});

test('Should send a put request and return the promise', () => {
    const promise = {};
    const data = {title: 'Title'};
    Requester.put.mockReturnValue(promise);
    const result = ResourceRequester.put('snippets', 5, data);
    expect(Requester.put).toBeCalledWith('/snippets/5', data);
    expect(result).toBe(promise);
});

test('Should send a put request with passed options as query parameters', () => {
    const data = {slogan: 'Slogan'};
    const options = {locale: 'en', action: 'publish'};
    Requester.put.mockReturnValue({});
    ResourceRequester.put('snippets', 5, data, options);
    expect(Requester.put).toBeCalledWith('/snippets/5?locale=en&action=publish', data);
});

test('Should send a delete request and return the promise', () => {
    const promise = {};
    Requester.delete.mockReturnValue(promise);
    const result = ResourceRequester.delete('snippets', 1);
    expect(result).toBe(promise);
});

test('Should send a delete request to the correct URL', () => {
    ResourceRequester.delete('snippets', 5);
    expect(Requester.delete).toBeCalledWith('/snippets/5');

    ResourceRequester.delete('contacts', 9);
    expect(Requester.delete).toBeCalledWith('/contacts/9');
});

test('Should send a delete request with passed options as query parameters', () => {
    const options = {locale: 'en', webspace: 'sulu'};
    Requester.delete.mockReturnValue({});
    ResourceRequester.delete('snippets', 5, options);
    expect(Requester.delete).toBeCalledWith('/snippets/5?locale=en&webspace=sulu');
});

test('Should send a post request and return the promise', () => {
    const promise = {};
    Requester.post.mockReturnValue(promise);
    const result = ResourceRequester.post('snippets', {});
    expect(Requester.post).toBeCalledWith('/snippets', {});
    expect(result).toBe(promise);
});

test('Should send a post request with an ID and return the promise', () => {
    const promise = {};
    Requester.post.mockReturnValue(promise);
    const result = ResourceRequester.postWithId('snippets', 5, {});
    expect(Requester.post).toBeCalledWith('/snippets/5', {});
    expect(result).toBe(promise);
});
