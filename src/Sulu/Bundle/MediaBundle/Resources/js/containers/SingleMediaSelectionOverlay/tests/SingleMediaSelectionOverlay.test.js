// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {observable} from 'mobx';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import getMockCallArg from 'sulu-admin-bundle/utils/TestHelper/getMockCallArg';
import SingleMediaSelectionOverlay from '../SingleMediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const React = require('react');
    const {observable: mobxObservable} = require('mobx');

    const MediaSelectionOverlay: any = jest.fn(function MediaSelectionOverlayMock() {
        return React.createElement('div', undefined, 'single media selection overlay');
    });

    MediaSelectionOverlay.createCollectionListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaListStore = jest.fn().mockReturnValue({
        selections: mobxObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
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
        <SingleMediaSelectionOverlay
            excludedIds={[66, 55]}
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
    expect(getMockCallArg(mediaSelectionOverlay.createMediaListStore, 0, 1).get()).toEqual([66, 55]);
    expect(mediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should create list-stores without excluded-ids', () => {
    const locale = observable.box('en');
    render(
        <SingleMediaSelectionOverlay
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

test('Should create list-stores with types', () => {
    const locale = observable.box('en');
    render(
        <SingleMediaSelectionOverlay
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            types={['image', 'video']}
        />
    );

    expect(mediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale,
        ['image', 'video']
    );
    expect(getMockCallArg(mediaSelectionOverlay.createMediaListStore, 0, 1).get()).toEqual(undefined);
    expect(mediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should update selections of media-list-store to only contain a single item', () => {
    render(
        <SingleMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    const mediaListStoreMock = getLastMediaSelectionOverlayProps().mediaListStore;

    mediaListStoreMock.selections.push({id: 3});
    expect(mediaListStoreMock.selections).toEqual([{id: 3}]);

    mediaListStoreMock.selections.push({id: 5});
    expect(mediaListStoreMock.clearSelection).toHaveBeenCalledWith();
    expect(mediaListStoreMock.select).toHaveBeenCalledWith({id: 5});
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {
        selections: observable([]),
        clear: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
        select: jest.fn(),
    };
    mediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = {destroy: jest.fn()};
    mediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const locale = observable.box('en');
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

    const props = getLastMediaSelectionOverlayProps();
    expect(props.mediaListStore).toEqual(mediaListStoreMock);
    expect(props.collectionListStore).toEqual(collectionListStoreMock);
    expect(props.locale).toEqual(locale);
    expect(props.open).toEqual(true);
    expect(props.onClose).toEqual(onClose);
});

test('Should clear media ListStore if the excludedIds prop is changed', () => {
    const {rerender} = render(
        <SingleMediaSelectionOverlay
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
        <SingleMediaSelectionOverlay
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
        <SingleMediaSelectionOverlay
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
        <SingleMediaSelectionOverlay
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
        <SingleMediaSelectionOverlay
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
