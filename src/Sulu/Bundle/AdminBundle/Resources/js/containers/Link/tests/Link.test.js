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
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';
import type {LinkValue} from '../types';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../components/SingleSelect/SingleSelect', () => {
    const React = require('react');

    const SingleSelect: any = jest.fn((props) => (
        <div data-testid="single-select">{props.children}</div>
    ));

    SingleSelect.Option = function Option(props) {
        return (
            <div data-testid="single-select-option" data-value={props.value}>
                {props.children}
            </div>
        );
    };

    return SingleSelect;
});

jest.mock('../overlays/LinkTypeOverlay', () => jest.fn(() => null));
jest.mock('../overlays/ExternalLinkTypeOverlay', () => jest.fn(() => null));

jest.mock('../registries/linkTypeRegistry', () => ({
    getKeys: jest.fn(),
    getOverlay: jest.fn(),
    getOptions: jest.fn(),
    getTitle: jest.fn((key) => key.charAt(0).toUpperCase() + (key.slice(1))),
}));

const SingleSelect = jest.requireMock('../../../components/SingleSelect/SingleSelect');
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

test('Open overlay on provider change', () => {
    configureLinkTypeRegistry(['page', 'media']);

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

    getLatestMockProps(SingleSelect).onChange('media');

    expect(
        getLatestOverlayProps(LinkTypeOverlayMock, (props) => props.options?.provider === 'media').open
    ).toEqual(true);
});

test('Update values on overlay confirm', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

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

    getLatestMockProps(SingleSelect).onChange('media');

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

test('Update values on overlay confirm with ExternalLinkTypeOverlay', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

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

    getLatestMockProps(SingleSelect).onChange('external');

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

    const removeButton = screen
        .getAllByRole('button')
        .find((button) => button.tagName.toLowerCase() === 'button');
    if (!removeButton) {
        throw new Error('Expected remove button to exist');
    }

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

test('Display providers with "types" property', () => {
    configureLinkTypeRegistry(['page', 'media', 'article']);

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

    expect(screen.getAllByTestId('single-select-option')).toHaveLength(2);
});

test('Display providers with "excluded_types" property', () => {
    configureLinkTypeRegistry(['page', 'media', 'article']);

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

    expect(screen.getAllByTestId('single-select-option')).toHaveLength(1);
});

test('Display providers with "excluded_types" and "types" property', () => {
    configureLinkTypeRegistry(['page', 'media', 'article', 'account']);

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

    expect(screen.getAllByTestId('single-select-option')).toHaveLength(2);
});
