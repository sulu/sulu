// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import copyToClipboard from 'copy-to-clipboard';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import PreviewStore from '../stores/PreviewStore';
import PreviewLinkPopover from '../PreviewLinkPopover';

jest.mock('copy-to-clipboard', () => jest.fn());

jest.mock('sulu-admin-bundle/components/Button', () => jest.fn(({children, onClick}) => (
    <button onClick={onClick} type="button">{children}</button>
)));

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

    await waitFor(() => expect(ResourceRequester.get).toBeCalledWith('preview_links', {
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    }));
    expect(screen.getByDisplayValue('/admin/p/123-123-123')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();

    await userEvent.click(screen.getByRole('button', {name: 'sulu_preview.copy'}));
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

    await waitFor(() => expect(ResourceRequester.get).toBeCalledWith('preview_links', {
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    }));
    expect(screen.getByRole('button', {name: 'sulu_preview.generate_link'})).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Generate link', async() => {
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

    expect(await screen.findByRole('button', {name: 'sulu_preview.generate_link'})).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', {name: 'sulu_preview.generate_link'}));

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
    expect(screen.getByDisplayValue('/admin/p/123-123-123')).toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Revoke Link', async() => {
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

    expect(await screen.findByRole('button', {name: 'sulu_preview.revoke'})).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', {name: 'sulu_preview.revoke'}));

    expect(getLatestMockProps(ResourceRequester.post)).toBe('preview_links');
    expect(getMockCallArg(ResourceRequester.post, 0, 1)).toStrictEqual({});
    expect(getMockCallArg(ResourceRequester.post, 0, 2)).toStrictEqual({
        action: 'revoke',
        resourceKey: 'pages',
        resourceId: '123-123-123',
        locale: 'de',
    });
});
