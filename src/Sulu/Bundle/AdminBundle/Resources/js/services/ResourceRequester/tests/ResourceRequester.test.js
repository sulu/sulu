// @flow
import 'url-search-params-polyfill';
import ResourceRequester from '../ResourceRequester';
import Requester from '../../Requester/Requester';
import resourceEndpointRegistry from '../registries/ResourceEndpointRegistry';

jest.mock('../../Requester/Requester', () => ({
    get: jest.fn(),
    put: jest.fn(),
    post: jest.fn(),
    delete: jest.fn(),
}));

jest.mock('../registries/ResourceEndpointRegistry', () => ({
    getDetailUrl: jest.fn(),
    getListUrl: jest.fn(),
}));

test('Should send a get request and return the promise', () => {
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets/5');
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.get('snippets', {id: 5});
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', {id: 5});
    expect(Requester.get).toBeCalledWith('/snippets/5');
    expect(result).toBe(promise);
});

test('Should send a get request without an ID and return the promise', () => {
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets');
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.get('snippets');
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', {});
    expect(Requester.get).toBeCalledWith('/snippets');
    expect(result).toBe(promise);
});

test('Should send a get request with passed options as query parameters', () => {
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets/5?locale=en&action=publish');
    const options = {id: 5, locale: 'en', action: 'publish'};
    ResourceRequester.get('snippets', options);
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', options);
    expect(Requester.get).toBeCalledWith('/snippets/5?locale=en&action=publish');
});

test('Should send a list get request and return the promise', () => {
    const promise = {};
    Requester.get.mockReturnValue(promise);
    const result = ResourceRequester.getList('snippets', {page: 1, limit: 10});
    expect(result).toBe(promise);
});

test('Should send a list get request to the correct URL with page and limit parameters', () => {
    resourceEndpointRegistry.getListUrl.mockImplementation((resourceKey, {page, limit}) => {
        return '/snippets?page=' + page + '&limit=' + limit + '&flat=true';
    });

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
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets/5');
    const promise = {};
    const data = {title: 'Title'};
    Requester.put.mockReturnValue(promise);
    const result = ResourceRequester.put('snippets', data, {id: 5});
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', {id: 5});
    expect(Requester.put).toBeCalledWith('/snippets/5', data);
    expect(result).toBe(promise);
});

test('Should send a put request with passed options as query parameters', () => {
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets/5?locale=en&action=publish');
    const data = {slogan: 'Slogan'};
    const options = {action: 'publish', id: 5, locale: 'en'};
    Requester.put.mockReturnValue({});
    ResourceRequester.put('snippets', data, options);
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', {action: 'publish', id: 5, locale: 'en'});
    expect(Requester.put).toBeCalledWith('/snippets/5?locale=en&action=publish', data);
});

test('Should send a delete request and return the promise', () => {
    const promise = {};
    Requester.delete.mockReturnValue(promise);
    const result = ResourceRequester.delete('snippets', {id: 1});
    expect(result).toBe(promise);
});

test('Should send a delete request to the correct URL', () => {
    resourceEndpointRegistry.getDetailUrl
        .mockImplementation((resourceKey, {id}) => '/' + resourceKey + '/' + id);

    ResourceRequester.delete('snippets', {id: 5});
    expect(Requester.delete).toBeCalledWith('/snippets/5');

    ResourceRequester.delete('contacts', {id: 9});
    expect(Requester.delete).toBeCalledWith('/contacts/9');
});

test('Should send a delete request with passed options as query parameters', () => {
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets/5?locale=en&webspace=sulu');
    const options = {id: 5, locale: 'en', webspace: 'sulu'};
    Requester.delete.mockReturnValue({});
    ResourceRequester.delete('snippets', options);
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', {id: 5, locale: 'en', webspace: 'sulu'});
    expect(Requester.delete).toBeCalledWith('/snippets/5?locale=en&webspace=sulu');
});

test('Should send a delete request and return the promise', () => {
    const promise = {};
    Requester.delete.mockReturnValue(promise);
    const result = ResourceRequester.deleteList('snippets', {ids: [3]});
    expect(result).toBe(promise);
});

test('Should send a collection delete request to the correct URL', () => {
    resourceEndpointRegistry.getListUrl
        .mockImplementation((resourceKey, {ids}) => '/' + resourceKey + '?ids=' + ids.join(','));

    ResourceRequester.deleteList('snippets', {ids: [1, 2, 3]});
    expect(Requester.delete).toBeCalledWith('/snippets?ids=1,2,3');

    ResourceRequester.deleteList('contacts', {ids: [4, 5, 6]});
    expect(Requester.delete).toBeCalledWith('/contacts?ids=4,5,6');
});

test('Should send a post request and return the promise', () => {
    resourceEndpointRegistry.getDetailUrl.mockReturnValue('/snippets');
    const promise = {};
    Requester.post.mockReturnValue(promise);
    const result = ResourceRequester.post('snippets', {});
    expect(resourceEndpointRegistry.getDetailUrl).toBeCalledWith('snippets', {});
    expect(Requester.post).toBeCalledWith('/snippets', {});
    expect(result).toBe(promise);
});
