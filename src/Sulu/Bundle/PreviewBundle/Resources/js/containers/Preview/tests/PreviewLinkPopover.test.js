// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

test('Render popover when preview link is available and copy link to clipboard', async() => {
    const user = userEvent.setup();
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
    const {container} = render(<PreviewLinkPopover previewStore={previewStore} />);

    expect(await screen.findByDisplayValue('/admin/p/123-123-123')).toBeInTheDocument();
    expect(container).toMatchSnapshot();

    expect(ResourceRequester.get).toHaveBeenCalledWith('preview_links', {
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    });

    await user.click(screen.getByRole('button', {name: 'sulu_preview.copy'}));

    expect(copyToClipboard).toHaveBeenCalledWith('/admin/p/123-123-123');
});

test('Render popover when no link is available', async() => {
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
    const {container} = render(<PreviewLinkPopover previewStore={previewStore} />);

    expect(await screen.findByRole('button', {name: 'sulu_preview.generate_link'})).toBeInTheDocument();
    expect(container).toMatchSnapshot();

    expect(ResourceRequester.get).toHaveBeenCalledWith('preview_links', {
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    });
});

test('Generate link', async() => {
    const user = userEvent.setup();
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
    const {container} = render(<PreviewLinkPopover previewStore={previewStore} />);

    expect(await screen.findByRole('button', {name: 'sulu_preview.generate_link'})).toBeInTheDocument();

    const generatePromise = Promise.resolve({
        token: '123-123-123',
    });
    ResourceRequester.post.mockReturnValue(generatePromise);

    await user.click(screen.getByRole('button', {name: 'sulu_preview.generate_link'}));
    expect(await screen.findByDisplayValue('/admin/p/123-123-123')).toBeInTheDocument();

    expect(container).toMatchSnapshot();

    expect(ResourceRequester.post).toHaveBeenCalledWith('preview_links', {}, {
        action: 'generate',
        dateTime: undefined,
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
        segmentKey: undefined,
        targetGroupId: undefined,
        webspaceKey: undefined,
    });
});

test('Revoke Link', async() => {
    const user = userEvent.setup();
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
    render(<PreviewLinkPopover previewStore={previewStore} />);

    expect(await screen.findByDisplayValue('/admin/p/123-123-123')).toBeInTheDocument();

    ResourceRequester.post.mockReturnValue(Promise.resolve());

    await user.click(screen.getByRole('button', {name: 'sulu_preview.revoke'}));

    await waitFor(() => expect(ResourceRequester.post).toHaveBeenCalledWith('preview_links', {}, {
        action: 'revoke',
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    }));
});
