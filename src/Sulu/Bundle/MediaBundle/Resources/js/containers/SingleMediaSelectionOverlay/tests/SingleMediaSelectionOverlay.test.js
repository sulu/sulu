// @flow
import {observable as mockObservable} from 'mobx';
import React from 'react';
import {render} from '@testing-library/react';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';
import SingleMediaSelectionOverlay from '../SingleMediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const MediaSelectionOverlay: any = jest.fn(() => <div>single media selection overlay</div>);
    MediaSelectionOverlay.createCollectionListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaListStore = jest.fn().mockReturnValue({
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
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
    const locale = mockObservable.box('en');
    render(
        <SingleMediaSelectionOverlay
            excludedIds={[66, 55]}
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
    expect(MediaSelectionOverlay.createMediaListStore.mock.calls[0][1].get()).toEqual([66, 55]);
    expect(MediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should create list-stores without excluded-ids', () => {
    const locale = mockObservable.box('en');
    render(
        <SingleMediaSelectionOverlay
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

test('Should create list-stores with types', () => {
    const locale = mockObservable.box('en');
    render(
        <SingleMediaSelectionOverlay
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            types={['image', 'video']}
        />
    );

    expect(MediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        ['image', 'video']
    );
    expect(MediaSelectionOverlay.createMediaListStore.mock.calls[0][1].get()).toEqual(undefined);
    expect(MediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should update selections of media-list-store to only contain a single item', () => {
    const mediaListStoreMock = {
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
        clear: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);

    render(
        <SingleMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={mockObservable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    mediaListStoreMock.selections.push({id: 3});
    expect(mediaListStoreMock.selections).toEqual([{id: 3}]);

    mediaListStoreMock.selections.push({id: 5});
    expect(mediaListStoreMock.clearSelection).toBeCalledWith();
    expect(mediaListStoreMock.select).toBeCalledWith({id: 5});
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
        clear: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = {
        destroy: jest.fn(),
    };
    MediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const locale = mockObservable.box('en');
    const onClose = jest.fn();

    render(
        <SingleMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={locale}
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    const mediaSelectionOverlayProps = getLatestMediaSelectionOverlayProps();

    expect(mediaSelectionOverlayProps.mediaListStore).toEqual(mediaListStoreMock);
    expect(mediaSelectionOverlayProps.collectionListStore).toEqual(collectionListStoreMock);
    expect(mediaSelectionOverlayProps.locale).toEqual(locale);
    expect(mediaSelectionOverlayProps.open).toEqual(true);
    expect(mediaSelectionOverlayProps.onClose).toEqual(onClose);
});

test('Should clear media ListStore if the excludedIds prop is changed', () => {
    const locale = mockObservable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const mediaListStoreMock = {
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
        clear: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);

    const {rerender} = render(
        <SingleMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).not.toBeCalled();

    rerender(
        <SingleMediaSelectionOverlay
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
    const locale = mockObservable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();
    const mediaListStoreMock = {
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
        clear: jest.fn(),
    };
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);

    const {rerender} = render(
        <SingleMediaSelectionOverlay
            excludedIds={[11, 22]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );

    expect(mediaListStoreMock.clear).not.toBeCalled();

    rerender(
        <SingleMediaSelectionOverlay
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
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
        clear: jest.fn(),
    };
    const collectionListStoreMock = {
        destroy: jest.fn(),
    };

    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    MediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const {unmount} = render(
        <SingleMediaSelectionOverlay
            excludedIds={[]}
            locale={mockObservable.box('en')}
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
