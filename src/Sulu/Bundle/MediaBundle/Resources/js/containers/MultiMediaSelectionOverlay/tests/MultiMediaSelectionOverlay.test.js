// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const React = require('react');

    const MediaSelectionOverlay: any = jest.fn(function MediaSelectionOverlayMock() {
        return React.createElement('div', undefined, 'single media selection overlay');
    });

    MediaSelectionOverlay.createCollectionListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
        clear: jest.fn(),
    });

    return MediaSelectionOverlay;
});

const mediaSelectionOverlay = ((jest.requireMock('../../MediaSelectionOverlay'): any): {
    createCollectionListStore: any,
    createMediaListStore: any,
    mock: {calls: Array<[Object]>},
    ...
});

function getLastMediaSelectionOverlayProps(): any {
    return getLatestMockProps(mediaSelectionOverlay);
}

test('Should create list-stores with correct locale and excluded-ids', () => {
    const locale = observable.box('en');
    render(
        <MultiMediaSelectionOverlay
            excludedIds={[44, 22]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        []
    );
    expect(getMockCallArg(mediaSelectionOverlay.createMediaListStore, 0, 1).get()).toEqual([44, 22]);
    expect(mediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should create list-stores without excluded-ids', () => {
    const locale = observable.box('en');
    render(
        <MultiMediaSelectionOverlay
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        []
    );
    expect(getMockCallArg(mediaSelectionOverlay.createMediaListStore, 0, 1).get()).toEqual(undefined);
    expect(mediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should create list-stores with correct media type', () => {
    const locale = observable.box('en');
    render(
        <MultiMediaSelectionOverlay
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            types={['image']}
        />
    );

    expect(mediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        ['image']
    );
    expect(getMockCallArg(mediaSelectionOverlay.createMediaListStore, 0, 1).get()).toEqual(undefined);
    expect(mediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {clear: jest.fn(), destroy: jest.fn()};
    mediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = {destroy: jest.fn()};
    mediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const locale = observable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();

    render(
        <MultiMediaSelectionOverlay
            confirmLoading={true}
            excludedIds={[22, 44]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    const props = getLastMediaSelectionOverlayProps();
    expect(props.confirmLoading).toEqual(true);
    expect(props.mediaListStore).toEqual(mediaListStoreMock);
    expect(props.collectionListStore).toEqual(collectionListStoreMock);
    expect(props.locale).toEqual(locale);
    expect(props.open).toEqual(true);
    expect(props.onClose).toEqual(onClose);
    expect(props.onConfirm).toEqual(onConfirm);
});

test('Should clear media ListStore if the excludedIds prop is changed', () => {
    const {rerender} = render(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    const mediaListStoreMock = getLastMediaSelectionOverlayProps().mediaListStore;

    expect(mediaListStoreMock.clear).not.toHaveBeenCalled();

    rerender(
        <MultiMediaSelectionOverlay
            excludedIds={[33]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).toHaveBeenCalled();
});

test('Should not clear media ListStore if new value of excludedIds prop is equal to old value', () => {
    const {rerender} = render(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    const mediaListStoreMock = getLastMediaSelectionOverlayProps().mediaListStore;

    expect(mediaListStoreMock.clear).not.toHaveBeenCalled();

    rerender(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).not.toHaveBeenCalled();
});

test('Should destroy list-stores on unmount', () => {
    const {unmount} = render(
        <MultiMediaSelectionOverlay
            excludedIds={[]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    const props = getLastMediaSelectionOverlayProps();

    expect(props.mediaListStore.destroy).not.toHaveBeenCalled();
    expect(props.collectionListStore.destroy).not.toHaveBeenCalled();

    unmount();

    expect(props.mediaListStore.destroy).toHaveBeenCalled();
    expect(props.collectionListStore.destroy).toHaveBeenCalled();
});
