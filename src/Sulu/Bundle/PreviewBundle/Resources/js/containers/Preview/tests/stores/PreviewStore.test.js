// @flow
import {Requester} from 'sulu-admin-bundle/services';
import {observable} from 'mobx';
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
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const requestPromise = Promise.resolve({token: '123-123-123'});
    Requester.post.mockReturnValue(requestPromise);

    previewStore.start();

    return requestPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages');
    });
});

test('Should request server without locale on start preview', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', undefined, 'sulu_io');

    const requestPromise = Promise.resolve({token: '123-123-123'});
    Requester.post.mockReturnValue(requestPromise);

    previewStore.start();

    return requestPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/start?id=123-123-123&provider=pages');
    });
});

test('Should request server on update preview', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.start();
    previewStore.update({title: 'Sulu is aswesome'}).then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            '/update?id=123-123-123&locale=en&provider=pages&targetGroupId=-1&webspaceKey=sulu_io',
            {data: {title: 'Sulu is aswesome'}}
        );
    });
});

test('Should request server on update preview with target group', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.setTargetGroup(2);
    previewStore.start();
    previewStore.update({title: 'Sulu is aswesome'}).then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            '/update?id=123-123-123&locale=en&provider=pages&targetGroupId=2&webspaceKey=sulu_io',
            {data: {title: 'Sulu is aswesome'}}
        );
    });
});

test('Should request server on update preview with date time', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.setDateTime(new Date(2020, 11, 10, 18, 50, 10));
    previewStore.start();
    previewStore.update({title: 'Sulu is aswesome'}).then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            '/update?dateTime=2020-12-10%2018%3A50&id=123-123-123&locale=en&provider=pages&targetGroupId=-1'
            + '&webspaceKey=sulu_io',
            {data: {title: 'Sulu is aswesome'}}
        );
    });
});

test('Should request server on update preview with segment', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.setSegment('w');
    previewStore.start();
    previewStore.update({title: 'Sulu is aswesome'}).then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith(
            '/update?id=123-123-123&locale=en&provider=pages&segmentKey=w&targetGroupId=-1&webspaceKey=sulu_io',
            {data: {title: 'Sulu is aswesome'}}
        );
    });
});

test('Should request server on update-context preview', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.start();
    previewStore.updateContext('default').then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post)
            .toBeCalledWith(
                '/update-context?id=123-123-123&locale=en&provider=pages&targetGroupId=-1&webspaceKey=sulu_io',
                {context: {template: 'default'}}
            );
    });
});

test('Should request server on update-context preview with target group', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.setTargetGroup(6);
    previewStore.start();
    previewStore.updateContext('default').then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post)
            .toBeCalledWith(
                '/update-context?id=123-123-123&locale=en&provider=pages&targetGroupId=6&webspaceKey=sulu_io',
                {context: {template: 'default'}}
            );
    });
});

test('Should request server on update-context preview with datetime', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.setDateTime(new Date(2020, 11, 10, 18, 50, 10));
    previewStore.start();
    previewStore.updateContext('default').then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post)
            .toBeCalledWith(
                '/update-context?dateTime=2020-12-10%2018%3A50&id=123-123-123&locale=en&provider=pages&targetGroupId=-1'
                + '&webspaceKey=sulu_io',
                {context: {template: 'default'}}
            );
    });
});

test('Should request server on update-context preview with segment', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});
    Requester.post.mockReturnValue(postPromise);

    previewStore.setSegment('s');
    previewStore.start();
    previewStore.updateContext('default').then((content) => {
        expect(content).toEqual('<h1>Sulu is awesome</h1>');
    });

    return postPromise.then(() => {
        expect(Requester.post)
            .toBeCalledWith(
                '/update-context' +
                '?id=123-123-123&locale=en&provider=pages&segmentKey=s&targetGroupId=-1&webspaceKey=sulu_io',
                {context: {template: 'default'}}
            );
    });
});

test('Should request server on stop preview', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');

    const postPromise = Promise.resolve({content: '<h1>Sulu is awesome</h1>'});

    previewStore.start();
    previewStore.stop();

    return postPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/start?id=123-123-123&locale=en&provider=pages');
        expect(Requester.post).toBeCalledWith('/stop');
    });
});

test('Should set webspace', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');
    expect(previewStore.webspace).toEqual('sulu_io');

    previewStore.setWebspace('example');
    expect(previewStore.webspace).toEqual('example');
});
