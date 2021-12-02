// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import {observable} from 'mobx';
import Link from '../Link';
import linkTypeRegistry from '../registries/linkTypeRegistry';
import LinkTypeOverlay from '../overlays/LinkTypeOverlay';
import type {LinkValue} from '../types';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../registries/linkTypeRegistry', () => ({
    getKeys: jest.fn(),
    getOverlay: jest.fn(),
    getOptions: jest.fn(),
}));

test('Render Link container', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue({title: 'Pages', overlayTitle: 'Test Overlay'});
    linkTypeRegistry.getKeys.mockReturnValue(['page']);

    const value: LinkValue = {
        title: 'TestLink',
        href: '123-asdf-123',
        provider: 'page',
        locale: 'en',
    };

    expect(
        render(
            <Link locale={observable.box('en')} onChange={changeSpy} onFinish={finishSpy} value={value} />
        )
    ).toMatchSnapshot();
});

test('Pass correct props to overlay', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const options = {
        title: 'Pages',
        overlayTitle: 'Test Overlay',
    };
    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue(options);
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

    // open overlay to properly check the href
    link.find('.item.clickable').simulate('click');

    expect(link.find('LinkTypeOverlay').props()).toMatchObject({
        href: '123-asdf-123',
        open: true,
        options,
        anchor: 'TestAnchor',
        target: 'TestTarget',
    });
});

test('Open overlay on input click', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const options = {
        title: 'Pages',
        overlayTitle: 'Test Overlay',
    };
    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue(options);
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

    const options = {
        title: 'Pages',
        overlayTitle: 'Test Overlay',
    };
    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue(options);
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

    const options = {
        title: 'Pages',
        overlayTitle: 'Test Overlay',
    };
    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue(options);
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

test('Invalidate values on RemoveButton click', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    const options = {
        title: 'Pages',
        overlayTitle: 'Test Overlay',
    };
    linkTypeRegistry.getOverlay.mockReturnValue(LinkTypeOverlay);
    linkTypeRegistry.getOptions.mockReturnValue(options);
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
});
