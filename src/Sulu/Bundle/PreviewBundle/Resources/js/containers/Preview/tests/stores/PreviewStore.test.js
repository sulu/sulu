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
        expect(Requester.get).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages&targetGroup=-1');
    });
});

test('Should request server on start preview with target group', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const requestPromise = Promise.resolve({token: '123-123-123'});
    Requester.get.mockReturnValue(requestPromise);

    previewStore.setTargetGroup(3);
    previewStore.start();

    return requestPromise.then(() => {
        expect(Requester.get).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages&targetGroup=3');
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
            '/update?locale=en&targetGroup=-1&webspace=sulu_io',
            {data: {title: 'Sulu is aswesome'}}
        );
    });
});

test('Should request server on update preview with target group', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const getPromise = Promise.resolve({token: '123-123-123'});
    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.get.mockReturnValue(getPromise);
    Requester.post.mockReturnValue(postPromise);

    previewStore.setTargetGroup(2);
    previewStore.start();
    previewStore.update({title: 'Sulu is aswesome'}).then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            '/update?locale=en&targetGroup=2&webspace=sulu_io',
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
        expect(Requester.post)
            .toBeCalledWith('/update-context?targetGroup=-1&webspace=sulu_io', {context: {template: 'default'}});
    });
});

test('Should request server on update-context preview with target group', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');

    const getPromise = Promise.resolve({token: '123-123-123'});
    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.get.mockReturnValue(getPromise);
    Requester.post.mockReturnValue(postPromise);

    previewStore.setTargetGroup(6);
    previewStore.start();
    previewStore.updateContext('default').then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post)
            .toBeCalledWith('/update-context?targetGroup=6&webspace=sulu_io', {context: {template: 'default'}});
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
        expect(Requester.get).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages&targetGroup=-1');
        expect(Requester.get).toBeCalledWith('/stop');
    });
});

test('Should set webspace', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', 'en', 'sulu_io');
    expect(previewStore.webspace).toEqual('sulu_io');

    previewStore.setWebspace('example');
    expect(previewStore.webspace).toEqual('example');
});
