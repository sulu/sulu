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
    jest.clearAllMocks();

    PreviewStore.endpoints = {
        'preview-link': '/admin/p/:token',
    };
});

test('Render popover when preview link is available and copy link to clipboard', async() => {
    const user = userEvent.setup();
    ResourceRequester.get.mockResolvedValue({
        token: '123-123-123',
    });

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const {asFragment} = render(<PreviewLinkPopover previewStore={previewStore} />);

    expect(await screen.findByRole('button', {name: 'sulu_preview.copy'})).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();

    expect(ResourceRequester.get).toBeCalledWith('preview_links', {
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    });

    await user.click(screen.getByRole('button', {name: 'sulu_preview.copy'}));
    expect(copyToClipboard).toBeCalledWith('/admin/p/123-123-123');
});

test('Render popover when no link is available', async() => {
    ResourceRequester.get.mockRejectedValue({
        status: 404,
    });

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const {asFragment} = render(<PreviewLinkPopover previewStore={previewStore} />);

    expect(await screen.findByRole('button', {name: 'sulu_preview.generate_link'})).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();

    expect(ResourceRequester.get).toBeCalledWith('preview_links', {
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    });
});

test('Generate link', async() => {
    const user = userEvent.setup();
    ResourceRequester.get.mockRejectedValue({
        status: 404,
    });
    ResourceRequester.post.mockResolvedValue({
        token: '123-123-123',
    });

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    const {asFragment} = render(<PreviewLinkPopover previewStore={previewStore} />);

    await user.click(await screen.findByRole('button', {name: 'sulu_preview.generate_link'}));

    await waitFor(() => expect(ResourceRequester.post).toBeCalledWith('preview_links', {}, {
        action: 'generate',
        dateTime: undefined,
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
        segmentKey: undefined,
        targetGroupId: undefined,
        webspaceKey: undefined,
    }));

    expect(await screen.findByRole('button', {name: 'sulu_preview.copy'})).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Revoke Link', async() => {
    const user = userEvent.setup();
    ResourceRequester.get.mockResolvedValue({
        token: '123-123-123',
    });
    ResourceRequester.post.mockResolvedValue();

    const previewStore = new PreviewStore(
        'pages',
        '123-123-123',
        observable.box('de'),
        'sulu_io',
        undefined
    );
    render(<PreviewLinkPopover previewStore={previewStore} />);

    await user.click(await screen.findByRole('button', {name: 'sulu_preview.revoke'}));

    expect(ResourceRequester.post.mock.calls[0][0]).toBe('preview_links');
    expect(ResourceRequester.post.mock.calls[0][1]).toStrictEqual({});
    expect(ResourceRequester.post.mock.calls[0][2]).toStrictEqual({
        action: 'revoke',
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    });
});
