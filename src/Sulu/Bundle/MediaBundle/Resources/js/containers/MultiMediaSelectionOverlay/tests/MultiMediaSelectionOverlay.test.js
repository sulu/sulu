// @flow
import {observable} from 'mobx';
import React from 'react';
import {render} from '@testing-library/react';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const MediaSelectionOverlay: any = jest.fn(() => <div>single media selection overlay</div>);
    MediaSelectionOverlay.createCollectionListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
        clear: jest.fn(),
    });

    return MediaSelectionOverlay;
});

function getLatestMediaSelectionOverlayProps() {
    const calls = (MediaSelectionOverlay: any).mock.calls;

    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

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

    expect(MediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        []
    );
    expect(MediaSelectionOverlay.createMediaListStore.mock.calls[0][1].get()).toEqual([44, 22]);
    expect(MediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
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

    expect(MediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        []
    );
    expect(MediaSelectionOverlay.createMediaListStore.mock.calls[0][1].get()).toEqual(undefined);
    expect(MediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
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

    expect(MediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        ['image']
    );
    expect(MediaSelectionOverlay.createMediaListStore.mock.calls[0][1].get()).toEqual(undefined);
    expect(MediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {
        clear: jest.fn(),
        destroy: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = {
        destroy: jest.fn(),
    };
    MediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

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
    const mediaSelectionOverlayProps = getLatestMediaSelectionOverlayProps();

    expect(mediaSelectionOverlayProps.confirmLoading).toEqual(true);
    expect(mediaSelectionOverlayProps.mediaListStore).toEqual(mediaListStoreMock);
    expect(mediaSelectionOverlayProps.collectionListStore).toEqual(collectionListStoreMock);
    expect(mediaSelectionOverlayProps.locale).toEqual(locale);
    expect(mediaSelectionOverlayProps.open).toEqual(true);
    expect(mediaSelectionOverlayProps.onClose).toEqual(onClose);
    expect(mediaSelectionOverlayProps.onConfirm).toEqual(onConfirm);
});

test('Should clear media ListStore if the excludedIds prop is changed', () => {
    const locale = observable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const mediaListStoreMock = {
        clear: jest.fn(),
        destroy: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);

    const {rerender} = render(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).not.toBeCalled();

    rerender(
        <MultiMediaSelectionOverlay
            excludedIds={[33]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).toBeCalled();
});

test('Should not clear media ListStore if new value of excludedIds prop is equal to old value', () => {
    const locale = observable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const mediaListStoreMock = {
        clear: jest.fn(),
        destroy: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);

    const {rerender} = render(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).not.toBeCalled();

    rerender(
        <MultiMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).not.toBeCalled();
});

test('Should destroy list-stores on unmount', () => {
    const mediaListStoreMock = {
        clear: jest.fn(),
        destroy: jest.fn(),
    };
    const collectionListStoreMock = {
        destroy: jest.fn(),
    };

    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    MediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const {unmount} = render(
        <MultiMediaSelectionOverlay
            excludedIds={[]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaListStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).not.toHaveBeenCalled();
    unmount();
    expect(mediaListStoreMock.destroy).toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).toHaveBeenCalled();
});
