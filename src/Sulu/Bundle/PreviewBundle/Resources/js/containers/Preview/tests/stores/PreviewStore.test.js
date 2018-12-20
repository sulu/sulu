// @flow
import {Requester} from 'sulu-admin-bundle/services';
import PreviewStore from '../../stores/PreviewStore';

PreviewStore.endpoints = {
    start: '/start',
    render: '/render',
    update: '/update',
    'update-context': '/update-context',
    stop: '/stop',
};

jest.mock('sulu-admin-bundle/services/Requester', () => ({
    get: jest.fn(),
    post: jest.fn(),
}));

test('Should request server on start preview', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const requestPromise = Promise.resolve({token: '123-123-123'});
    Requester.get.mockReturnValue(requestPromise);

    previewStore.start();

    return requestPromise.then(() => {
        expect(Requester.get).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages');
    });
});

test('Should request server on update preview', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const getPromise = Promise.resolve({token: '123-123-123'});
    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.get.mockReturnValue(getPromise);
    Requester.post.mockReturnValue(postPromise);

    previewStore.start();
    previewStore.update({title: 'Sulu is aswesome'}).then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            '/update?locale=en&webspace=sulu_io',
            {data: {title: 'Sulu is aswesome'}}
        );
    });
});

test('Should request server on update-context preview', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const getPromise = Promise.resolve({token: '123-123-123'});
    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.get.mockReturnValue(getPromise);
    Requester.post.mockReturnValue(postPromise);

    previewStore.start();
    previewStore.updateContext('default').then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/update-context?webspace=sulu_io', {context: {template: 'default'}});
    });
});

test('Should request server on stop preview', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const getPromise = Promise.resolve({token: '123-123-123'});
    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.get.mockReturnValue(getPromise);

    previewStore.start();
    previewStore.stop();

    return postPromise.then(() => {
        expect(Requester.get).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages');
        expect(Requester.get).toBeCalledWith('/stop');
    });
});
