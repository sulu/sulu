// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {observable} from 'mobx';
import SingleSelectComponent from '../../../components/SingleSelect/SingleSelect';
import {ResourceRequester} from '../../../services';
import Link from '../Link';
import linkTypeRegistry from '../registries/linkTypeRegistry';
import LinkTypeOverlay from '../overlays/LinkTypeOverlay';
import ExternalLinkTypeOverlay from '../overlays/ExternalLinkTypeOverlay';
import type {LinkValue} from '../types';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../components/SingleSelect/SingleSelect', () => {
    const React = require('react');
    const SingleSelect: any = jest.fn(function SingleSelect(props) {
        return <div>{props.children}</div>;
    });

    SingleSelect.Option = jest.fn(function Option(props) {
        return <div>{props.children}</div>;
    });

    return SingleSelect;
});

jest.mock('../overlays/LinkTypeOverlay', () => jest.fn(() => null));
jest.mock('../overlays/ExternalLinkTypeOverlay', () => jest.fn(() => null));

jest.mock('../registries/linkTypeRegistry', () => ({
    getKeys: jest.fn(),
    getOverlay: jest.fn(),
    getOptions: jest.fn(),
    getTitle: jest.fn((key) => key.charAt(0).toUpperCase() + key.slice(1)),
}));

function getLatestSingleSelectProps() {
    const calls = (SingleSelectComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestOverlayProps(OverlayComponent: any) {
    const calls = OverlayComponent.mock.calls;

    return calls[calls.length - 1][0];
}

function getRenderedOptionValues() {
    const calls = ((SingleSelectComponent: any).Option: any).mock.calls;

    return calls.map((call) => call[0].value);
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render Link container incl. loading a selected value', async() => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page']);

    const getPromise = Promise.resolve({title: 'Page 1'});
    ResourceRequester.get.mockReturnValue(getPromise);

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
    };

    const {asFragment} = render(
        <Link locale={observable.box('en')} onChange={changeSpy} onFinish={finishSpy} value={value} />
    );

    await waitFor(() => {
        expect(screen.getByText('Page 1')).toBeInTheDocument();
    });

    expect(asFragment()).toMatchSnapshot();
});

test('Open overlay on input click', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page']);

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

    expect(getLatestOverlayProps(LinkTypeOverlay).open).toEqual(false);
    await user.click(screen.getByRole('button', {name: '…'}));
    expect(getLatestOverlayProps(LinkTypeOverlay).open).toEqual(true);
});

test('Open overlay on provider change', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media']);

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

    expect(getLatestOverlayProps(LinkTypeOverlay).open).toEqual(false);
    getLatestSingleSelectProps().onChange('media');
    expect(getLatestOverlayProps(LinkTypeOverlay).open).toEqual(true);
});

test('Update values on overlay confirm', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media']);

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

    getLatestSingleSelectProps().onChange('media');

    const overlayProps = getLatestOverlayProps(LinkTypeOverlay);
    overlayProps.onHrefChange('10');
    overlayProps.onQueryChange('newQuery');
    overlayProps.onAnchorChange('newAnchor');
    overlayProps.onTargetChange('newTarget');
    overlayProps.onTitleChange('newTitle');

    overlayProps.onConfirm();

    expect(changeSpy).toBeCalledWith({
        title: 'newTitle',
        href: '10',
        provider: 'media',
        locale: 'en',
        query: 'newQuery',
        anchor: 'newAnchor',
        target: 'newTarget',
    });
});

test('Update values on overlay confirm with ExternalLinkTypeOverlay', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(ExternalLinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue(undefined);
    linkTypeRegistry.getKeys.mockReturnValue(['media', 'external']);

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

    getLatestSingleSelectProps().onChange('external');

    const overlayProps = getLatestOverlayProps(ExternalLinkTypeOverlay);
    overlayProps.onHrefChange('https://example.org');
    overlayProps.onTargetChange('newTarget');
    overlayProps.onTitleChange('newTitle');
    overlayProps.onRelChange('newRel');

    overlayProps.onConfirm();

    expect(changeSpy).toBeCalledWith({
        title: 'newTitle',
        href: 'https://example.org',
        provider: 'external',
        locale: 'en',
        target: 'newTarget',
        rel: 'newRel',
    });
});

test('Invalidate values on RemoveButton click', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media']);

    const getPromise = Promise.resolve({title: 'Page 1'});
    ResourceRequester.get.mockReturnValue(getPromise);

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

    await waitFor(() => {
        expect(screen.getByText('Page 1')).toBeInTheDocument();
    });

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(changeSpy).toBeCalledWith({
        title: undefined,
        href: undefined,
        provider: undefined,
        locale: 'en',
        query: undefined,
        anchor: undefined,
        target: undefined,
        rel: undefined,
    });
});

test('Display providers with "types" property', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media', 'article']);

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={['page', 'article']}
            value={undefined}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-trash-alt'}));

    expect(getRenderedOptionValues()).toEqual(['page', 'article']);
});

test('Display providers with "excluded_types" property', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media', 'article']);

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            excludedTypes={['page', 'article']}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={undefined}
        />
    );

    expect(getRenderedOptionValues()).toEqual(['media']);
});

test('Display providers with "excluded_types" and "types" property', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({
        title: 'Pages',
        overlayTitle: 'Test Overlay',
        resourceKey: 'pages',
        displayProperties: ['title'],
    });
    linkTypeRegistry.getKeys.mockReturnValue(['page', 'media', 'article', 'account']);

    render(
        <Link
            enableAnchor={true}
            enableRel={true}
            enableTarget={true}
            enableTitle={true}
            excludedTypes={['page', 'article']}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={['media', 'account']}
            value={undefined}
        />
    );

    expect(getRenderedOptionValues()).toEqual(['media', 'account']);
});
