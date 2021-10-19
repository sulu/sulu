// @flow
import React from 'react';
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import copyToClipboard from 'copy-to-clipboard';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import PreviewStore from '../stores/PreviewStore';
import PreviewLinkPopover from '../PreviewLinkPopover';

jest.mock('copy-to-clipboard', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
    post: jest.fn(),
}));

jest.mock('../stores/PreviewStore', () => jest.fn(function(resourceKey, id, locale) {
    this.resourceKey = resourceKey;
    this.id = id;
    this.locale = locale.get();
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

beforeEach(() => {
    jest.resetModules();

    PreviewStore.endpoints = {
        'preview-link': '/admin/p/:token',
    };
});

test('Render popover when preview link is available and copy link to clipboard', () => {
    const promise = Promise.resolve({
        token: '123-123-123',
    });
    ResourceRequester.get.mockReturnValue(promise);

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const component = shallow(<PreviewLinkPopover previewStore={previewStore} />);

    return promise.then(() => {
        expect(component).toMatchSnapshot();

        expect(ResourceRequester.get).toBeCalledWith('preview_links', {
            resourceKey: 'pages',
            resourceId: '123-123-123',
            locale: 'de',
        });

        component.instance().handleCopyClick();

        expect(copyToClipboard).toBeCalledWith('/admin/p/123-123-123');
    });
});

test('Render popover when no link is available', (done) => {
    const promise = Promise.reject({
        status: 404,
    });
    ResourceRequester.get.mockReturnValue(promise);

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const component = shallow(<PreviewLinkPopover previewStore={previewStore} />);

    setTimeout(() => {
        expect(component).toMatchSnapshot();

        expect(ResourceRequester.get).toBeCalledWith('preview_links', {
            resourceKey: 'pages',
            resourceId: '123-123-123',
            locale: 'de',
        });

        done();
    });
});

test('Generate link', (done) => {
    const promise = Promise.reject({
        status: 404,
    });
    ResourceRequester.get.mockReturnValue(promise);

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const component = shallow(<PreviewLinkPopover previewStore={previewStore} />);

    setTimeout(() => {
        const promise = Promise.resolve({
            token: '123-123-123',
        });
        ResourceRequester.post.mockReturnValue(promise);

        component.instance().handleGenerateClick();

        setTimeout(() => {
            expect(component).toMatchSnapshot();

            expect(ResourceRequester.post).toBeCalledWith('preview_links', {}, {
                action: 'generate',
                dateTime: undefined,
                resourceKey: 'pages',
                resourceId: '123-123-123',
                locale: 'de',
                segmentKey: undefined,
                targetGroupId: undefined,
                webspaceKey: undefined,
            });

            done();
        });
    });
});

test('Revoke Link', (done) => {
    const promise = Promise.resolve({
        token: '123-123-123',
    });
    ResourceRequester.get.mockReturnValue(promise);

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const component = shallow(<PreviewLinkPopover previewStore={previewStore} />);

    promise.then(() => {
        const promise = Promise.resolve();
        ResourceRequester.post.mockReturnValue(promise);

        component.instance().handleRevokeClick({preventDefault: jest.fn()});

        expect(ResourceRequester.post.mock.calls[0][0]).toBe('preview_links');
        expect(ResourceRequester.post.mock.calls[0][1]).toStrictEqual({});
        expect(ResourceRequester.post.mock.calls[0][2]).toStrictEqual({
            action: 'revoke',
            resourceKey: 'pages',
            resourceId: '123-123-123',
            locale: 'de',
        });

        done();
    });
});
