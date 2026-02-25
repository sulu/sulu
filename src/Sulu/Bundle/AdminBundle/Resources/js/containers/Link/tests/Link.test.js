// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceRequester} from '../../../services';
import Link from '../Link';
import linkTypeRegistry from '../registries/linkTypeRegistry';
import LinkTypeOverlay from '../overlays/LinkTypeOverlay';
import ExternalLinkTypeOverlay from '../overlays/ExternalLinkTypeOverlay';
import findMockCallArg from '../../../utils/TestHelper/findMockCallArg';
import type {LinkValue} from '../types';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../overlays/LinkTypeOverlay', () => jest.fn(() => null));
jest.mock('../overlays/ExternalLinkTypeOverlay', () => jest.fn(() => null));

jest.mock('../registries/linkTypeRegistry', () => ({
    getKeys: jest.fn(),
    getOverlay: jest.fn(),
    getOptions: jest.fn(),
    getTitle: jest.fn((key) => key.charAt(0).toUpperCase() + (key.slice(1))),
}));

const LinkTypeOverlayMock: any = jest.requireMock('../overlays/LinkTypeOverlay');
const ExternalLinkTypeOverlayMock: any = jest.requireMock('../overlays/ExternalLinkTypeOverlay');

const createOverlayOptions = (provider) => ({
    displayProperties: ['title'],
    overlayTitle: 'Test Overlay',
    provider,
    resourceKey: 'pages',
    title: 'Pages',
});

const configureLinkTypeRegistry = (keys) => {
    linkTypeRegistry.getKeys.mockReturnValue(keys);
    linkTypeRegistry.getOverlay.mockImplementation(
        (key) => key === 'external' ? ExternalLinkTypeOverlay : LinkTypeOverlay
    );
    linkTypeRegistry.getOptions.mockImplementation((key) => key === 'external' ? undefined : createOverlayOptions(key));
};

const getLatestOverlayProps = (OverlayComponent: any, matcher: (props: Object) => boolean = () => true) => {
    return findMockCallArg(OverlayComponent, ([props]) => matcher(props));
};

function getProviderSelectButton() {
    const providerButton = screen.getAllByRole('button')
        .find((button) => (
            /(Page|Media|Article|Account|External|sulu_admin\.please_choose)/.test(button.textContent || '')
        ));

    if (!providerButton) {
        throw new Error('Expected provider select button to exist');
    }

    return providerButton;
}

async function selectProvider(label: string, user: any) {
    await user.click(getProviderSelectButton());
    await user.click(screen.getByRole('button', {name: new RegExp(`^${label}$`)}));
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render Link container incl. loading a selected value', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    configureLinkTypeRegistry(['page']);
    ResourceRequester.get.mockResolvedValue({title: 'Page 1'});

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
    };

    const {asFragment} = render(
        <Link locale={observable.box('en')} onChange={changeSpy} onFinish={finishSpy} value={value} />
    );

    await waitFor(() => expect(ResourceRequester.get).toBeCalled());

    expect(asFragment()).toMatchSnapshot();
});

test('Open overlay on input click', async() => {
    const user = userEvent.setup();

    configureLinkTypeRegistry(['page']);
    ResourceRequester.get.mockResolvedValue({title: 'Page 1'});

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        anchor: 'TestAnchor',
        target: 'TestTarget',
        rel: 'TestRel',
    };

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(getLatestOverlayProps(LinkTypeOverlayMock).open).toEqual(false);

    const titleButton = screen
        .getAllByRole('button')
        .find((button) => button.tagName.toLowerCase() !== 'button');
    if (!titleButton) {
        throw new Error('Expected title button to exist');
    }

    await user.click(titleButton);

    expect(getLatestOverlayProps(LinkTypeOverlayMock).open).toEqual(true);
});

test('Open overlay on provider change', async() => {
    configureLinkTypeRegistry(['page', 'media']);
    const user = userEvent.setup();

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        anchor: 'TestAnchor',
        target: 'TestTarget',
        rel: 'TestRel',
    };

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={value}
        />
    );

    expect(
        getLatestOverlayProps(LinkTypeOverlayMock, (props) => props.options?.provider === 'media').open
    ).toEqual(false);

    await selectProvider('Media', user);

    expect(
        getLatestOverlayProps(LinkTypeOverlayMock, (props) => props.options?.provider === 'media').open
    ).toEqual(true);
});

test('Update values on overlay confirm', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const user = userEvent.setup();

    configureLinkTypeRegistry(['page', 'media']);

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        query: 'TestQuery',
        anchor: 'TestAnchor',
        target: 'TestTarget',
    };

    render(
        <Link
            enableAnchor={true}
            enableQuery={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    await selectProvider('Media', user);

    const overlayProps = getLatestOverlayProps(LinkTypeOverlayMock, (props) => props.options?.provider === 'media');
    overlayProps.onHrefChange('10');
    overlayProps.onQueryChange('newQuery');
    overlayProps.onAnchorChange('newAnchor');
    overlayProps.onTargetChange('newTarget');
    overlayProps.onTitleChange('newTitle');
    overlayProps.onConfirm();

    expect(changeSpy).toBeCalledWith(
        {
            title: 'newTitle',
            href: '10',
            provider: 'media',
            locale: 'en',
            query: 'newQuery',
            anchor: 'newAnchor',
            target: 'newTarget',
            rel: undefined,
        }
    );
    expect(finishSpy).toBeCalled();
});

test('Update values on overlay confirm with ExternalLinkTypeOverlay', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const user = userEvent.setup();

    configureLinkTypeRegistry(['media', 'external']);

    const value: LinkValue = {
        title: 'TestLink',
        href: '10',
        provider: 'media',
        locale: 'en',
        target: 'TestTarget',
    };

    render(
        <Link
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    await selectProvider('External', user);

    const overlayProps = getLatestOverlayProps(ExternalLinkTypeOverlayMock);
    overlayProps.onHrefChange('https://example.org');
    overlayProps.onTargetChange('newTarget');
    overlayProps.onTitleChange('newTitle');
    overlayProps.onRelChange('newRel');
    overlayProps.onConfirm();

    expect(changeSpy).toBeCalledWith(
        {
            title: 'newTitle',
            href: 'https://example.org',
            provider: 'external',
            locale: 'en',
            target: 'newTarget',
            rel: 'newRel',
            anchor: undefined,
            query: undefined,
        }
    );
    expect(finishSpy).toBeCalled();
});

test('Invalidate values on RemoveButton click', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    configureLinkTypeRegistry(['page', 'media']);
    ResourceRequester.get.mockResolvedValue({title: 'Page 1'});

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
        anchor: 'TestAnchor',
        target: 'TestTarget',
        rel: 'TestRel',
    };

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />
    );

    await waitFor(() => expect(ResourceRequester.get).toBeCalled());

    const removeButton = screen.getByRole('button', {name: /su-trash-alt/});

    await user.click(removeButton);

    expect(changeSpy).toBeCalledWith(
        {
            title: undefined,
            href: undefined,
            provider: undefined,
            locale: 'en',
            query: undefined,
            anchor: undefined,
            target: undefined,
            rel: undefined,
        }
    );
    expect(finishSpy).toBeCalled();
});

test('Display providers with "types" property', async() => {
    configureLinkTypeRegistry(['page', 'media', 'article']);
    const user = userEvent.setup();

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            types={['page', 'article']}
            value={undefined}
        />
    );

    await user.click(getProviderSelectButton());

    expect(screen.getByRole('button', {name: 'Page'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Article'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Media'})).not.toBeInTheDocument();
});

test('Display providers with "excluded_types" property', async() => {
    configureLinkTypeRegistry(['page', 'media', 'article']);
    const user = userEvent.setup();

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            excludedTypes={['page', 'article']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            value={undefined}
        />
    );

    await user.click(getProviderSelectButton());

    expect(screen.getByRole('button', {name: 'Media'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Page'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Article'})).not.toBeInTheDocument();
});

test('Display providers with "excluded_types" and "types" property', async() => {
    configureLinkTypeRegistry(['page', 'media', 'article', 'account']);
    const user = userEvent.setup();

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            excludedTypes={['page', 'article']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            types={['media', 'account']}
            value={undefined}
        />
    );

    await user.click(getProviderSelectButton());

    expect(screen.getByRole('button', {name: 'Media'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Account'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Page'})).not.toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Article'})).not.toBeInTheDocument();
});
