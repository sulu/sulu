// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import {ResourceRequester} from '../../../services';
import Link from '../Link';
import linkTypeRegistry from '../registries/linkTypeRegistry';
import LinkTypeOverlay from '../overlays/LinkTypeOverlay';
import type {LinkValue} from '../types';

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../registries/linkTypeRegistry', () => ({
    getKeys: jest.fn(),
    getOverlay: jest.fn(),
    getOptions: jest.fn(),
    getTitle: jest.fn((key) => key.charAt(0).toUpperCase() + (key.slice(1))),
}));

test('Render Link container incl. loading a selected value', async(resolve) => {
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

    const link = shallow(
        <Link locale={observable.box('en')} onChange={changeSpy} onFinish={finishSpy} value={value} />
    );

    getPromise.finally(() => {
        setTimeout(() => {
            expect(link).toMatchSnapshot();

            resolve();
        }, 0);
    });
});

test('Open overlay on input click', () => {
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
    };

    const link = shallow(
        <Link
            enableAnchor={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />);

    const button = link.find('.item.clickable');

    expect(link.find('LinkTypeOverlay').props().open).toEqual(false);
    button.simulate('click');
    expect(link.find('LinkTypeOverlay').props().open).toEqual(true);
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
    };

    const link = shallow(
        <Link
            enableAnchor={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />);

    expect(link.find('LinkTypeOverlay').at(1).props().open).toEqual(false);
    link.find('SingleSelect').props().onChange('media');
    expect(link.find('LinkTypeOverlay').at(1).props().open).toEqual(true);
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
        anchor: 'TestAnchor',
        target: 'TestTarget',
    };

    const link = shallow(
        <Link
            enableAnchor={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={value}
        />);

    link.find('SingleSelect').props().onChange('media');

    const overlayProps = link.find('LinkTypeOverlay').at(1).props();
    overlayProps.onHrefChange('10');
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
            anchor: 'newAnchor',
            target: 'newTarget',
        }
    );
});

test('Invalidate values on RemoveButton click', async(resolve) => {
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
    };

    const link = shallow(<Link
        enableAnchor={true}
        enableTarget={true}
        enableTitle={true}
        locale={observable.box('en')}
        onChange={changeSpy}
        onFinish={finishSpy}
        value={value}
    />);

    getPromise.finally(() => {
        setTimeout(() => {
            const removeButton = link.find('.removeButton');
            removeButton.simulate('click');

            expect(changeSpy).toBeCalledWith(
                {
                    title: undefined,
                    href: undefined,
                    provider: undefined,
                    locale: 'en',
                    anchor: undefined,
                    target: undefined,
                }
            );

            resolve();
        }, 0);
    });
});

test('Display providers with "types" property', () => {
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

    const link = shallow(
        <Link
            enableAnchor={true}
            enableTarget={true}
            enableTitle={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={['page', 'article']}
            value={undefined}
        />);

    const removeButton = link.find('.removeButton');
    removeButton.simulate('click');
    expect(link.find('Option').length).toEqual(2);
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

    const link = shallow(
        <Link
            enableAnchor={true}
            enableTarget={true}
            enableTitle={true}
            excludedTypes={['page', 'article']}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={undefined}
        />);

    expect(link.find('Option').length).toEqual(1);
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

    const link = shallow(
        <Link
            enableAnchor={true}
            enableTarget={true}
            enableTitle={true}
            excludedTypes={['page', 'article']}
            locale={observable.box('en')}
            onChange={changeSpy}
            onFinish={finishSpy}
            types={['media', 'account']}
            value={undefined}
        />);

    expect(link.find('Option').length).toEqual(2);
});
