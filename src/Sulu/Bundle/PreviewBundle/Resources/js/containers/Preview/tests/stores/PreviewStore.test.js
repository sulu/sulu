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
        expect(Requester.post).toBeCalledWith('/start?provider=pages&id=123-123-123&locale=en');
    });
});

test('Should request server without locale on start preview', () => {
    const previewStore = new PreviewStore('pages', '123-123-123', undefined, 'sulu_io');

    const requestPromise = Promise.resolve({token: '123-123-123'});
    Requester.post.mockReturnValue(requestPromise);

    previewStore.start();

    return requestPromise.then(() => {
        expect(Requester.post).toBeCalledWith('/start?provider=pages&id=123-123-123');
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
            '/update?locale=en&webspaceKey=sulu_io&provider=pages&id=123-123-123&targetGroupId=-1',
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
            '/update?locale=en&webspaceKey=sulu_io&provider=pages&id=123-123-123&targetGroupId=2',
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
            '/update?locale=en&webspaceKey=sulu_io&provider=pages&id=123-123-123&targetGroupId=-1'
            + '&dateTime=2020-12-10+18%3A50',
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
            '/update?locale=en&webspaceKey=sulu_io&segmentKey=w&provider=pages&id=123-123-123&targetGroupId=-1',
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
                '/update-context?webspaceKey=sulu_io&locale=en&provider=pages&id=123-123-123&targetGroupId=-1',
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
                '/update-context?webspaceKey=sulu_io&locale=en&provider=pages&id=123-123-123&targetGroupId=6',
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
                '/update-context?webspaceKey=sulu_io&locale=en&provider=pages&id=123-123-123&targetGroupId=-1'
                + '&dateTime=2020-12-10+18%3A50',
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
                '?webspaceKey=sulu_io&segmentKey=s&locale=en&provider=pages&id=123-123-123&targetGroupId=-1',
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
        expect(Requester.post).toBeCalledWith('/start?provider=pages&id=123-123-123&locale=en');
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

test('Should request server on restart preview with new locale', () => {
    const locale = observable.box('en');
    const previewStore = new PreviewStore('pages', '123-123-123', locale, 'sulu_io');
    previewStore.start();

    locale.set('de');

    // $FlowFixMe
    Requester.post = jest.fn();
    const startPromise = Promise.resolve({token: '123-123-123'});
    const stopPromise = Promise.resolve();
    Requester.post.mockReturnValueOnce(stopPromise);
    Requester.post.mockReturnValueOnce(startPromise);

    return previewStore.restart().then(() => {
        expect(Requester.post).toBeCalledWith('/stop');
        expect(Requester.post).toBeCalledWith('/start?provider=pages&id=123-123-123&locale=de');
    });
});
