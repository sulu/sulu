// @flow
import {observable} from 'mobx';
import React from 'react';
import {render} from '@testing-library/react';
import {renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const MediaSelectionOverlay: any = jest.fn(function() {
        return <div>single media selection overlay</div>;
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

const MediaSelectionOverlayMock = (MediaSelectionOverlay: any);

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

    expect(MediaSelectionOverlayMock.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        []
    );
    expect(MediaSelectionOverlayMock.createMediaListStore.mock.calls[0][1].get()).toEqual([44, 22]);
    expect(MediaSelectionOverlayMock.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
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

    expect(MediaSelectionOverlayMock.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        []
    );
    expect(MediaSelectionOverlayMock.createMediaListStore.mock.calls[0][1].get()).toEqual(undefined);
    expect(MediaSelectionOverlayMock.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
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

    expect(MediaSelectionOverlayMock.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        ['image']
    );
    expect(MediaSelectionOverlayMock.createMediaListStore.mock.calls[0][1].get()).toEqual(undefined);
    expect(MediaSelectionOverlayMock.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {clear: jest.fn(), destroy: jest.fn()};
    MediaSelectionOverlayMock.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = {destroy: jest.fn()};
    MediaSelectionOverlayMock.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

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

    expect(MediaSelectionOverlayMock.mock.calls[0][0].confirmLoading).toEqual(true);
    expect(MediaSelectionOverlayMock.mock.calls[0][0].mediaListStore).toEqual(mediaListStoreMock);
    expect(MediaSelectionOverlayMock.mock.calls[0][0].collectionListStore).toEqual(collectionListStoreMock);
    expect(MediaSelectionOverlayMock.mock.calls[0][0].locale).toEqual(locale);
    expect(MediaSelectionOverlayMock.mock.calls[0][0].open).toEqual(true);
    expect(MediaSelectionOverlayMock.mock.calls[0][0].onClose).toEqual(onClose);
    expect(MediaSelectionOverlayMock.mock.calls[0][0].onConfirm).toEqual(onConfirm);
});

test('Should clear media ListStore if the excludedIds prop is changed', () => {
    const {instance: multiMediaSelectionOverlay, rerender} = renderWithRef(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(multiMediaSelectionOverlay.mediaListStore.clear).not.toHaveBeenCalled();

    rerender(
        <MultiMediaSelectionOverlay
            excludedIds={[33]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(multiMediaSelectionOverlay.mediaListStore.clear).toHaveBeenCalled();
});

test('Should not clear media ListStore if new value of excludedIds prop is equal to old value', () => {
    const {instance: multiMediaSelectionOverlay, rerender} = renderWithRef(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(multiMediaSelectionOverlay.mediaListStore.clear).not.toHaveBeenCalled();

    rerender(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(multiMediaSelectionOverlay.mediaListStore.clear).not.toHaveBeenCalled();
});

test('Should destroy list-stores on unmount', () => {
    const {instance: multiMediaSelectionOverlay, unmount} = renderWithRef(
        <MultiMediaSelectionOverlay
            excludedIds={[]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const mediaListStoreMock = multiMediaSelectionOverlay.mediaListStore;
    const collectionListStoreMock = multiMediaSelectionOverlay.collectionListStore;

    expect(mediaListStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).not.toHaveBeenCalled();
    unmount();
    expect(mediaListStoreMock.destroy).toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).toHaveBeenCalled();
});
