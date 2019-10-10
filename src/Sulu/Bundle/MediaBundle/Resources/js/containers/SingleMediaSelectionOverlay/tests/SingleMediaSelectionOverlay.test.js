// @flow
import {shallow} from 'enzyme';
import {observable as mockObservable} from 'mobx';
import React from 'react';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';
import SingleMediaSelectionOverlay from '../SingleMediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const MediaSelectionOverlay = function() {
        return <div>single media selection overlay</div>;
    };
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

test('Should create list-stores with correct locale and excluded-ids', () => {
    const locale = mockObservable.box('en');
    shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[66, 55]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).render();

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
    shallow(
        <SingleMediaSelectionOverlay
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).render();

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
    shallow(
        <SingleMediaSelectionOverlay
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            types={['image', 'video']}
        />
    ).render();

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
    const singleMediaSelectionOverlay = shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={mockObservable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    singleMediaSelectionOverlay.instance().mediaListStore.selections.push({id: 3});
    expect(singleMediaSelectionOverlay.instance().mediaListStore.selections).toEqual([{id: 3}]);

    singleMediaSelectionOverlay.instance().mediaListStore.selections.push({id: 5});
    expect(singleMediaSelectionOverlay.instance().mediaListStore.clearSelection).toBeCalledWith();
    expect(singleMediaSelectionOverlay.instance().mediaListStore.select).toBeCalledWith({id: 5});
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {selections: mockObservable([]), clear: jest.fn()};
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = jest.fn();
    MediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const locale = mockObservable.box('en');
    const onClose = jest.fn();

    const singleMediaSelectionOverlay = shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={locale}
            onClose={onClose}
            onConfirm={jest.fn()}
            open={true}
        />
    );
    const mediaSelectionOverlay = singleMediaSelectionOverlay.find(MediaSelectionOverlay);

    expect(mediaSelectionOverlay.prop('mediaListStore')).toEqual(mediaListStoreMock);
    expect(mediaSelectionOverlay.prop('collectionListStore')).toEqual(collectionListStoreMock);
    expect(mediaSelectionOverlay.prop('locale')).toEqual(locale);
    expect(mediaSelectionOverlay.prop('open')).toEqual(true);
    expect(mediaSelectionOverlay.prop('onClose')).toEqual(onClose);
});

test('Should destroy list-stores on unmount', () => {
    const singleMediaSelectionOverlay = shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[]}
            locale={mockObservable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const mediaListStoreMock = singleMediaSelectionOverlay.instance().mediaListStore;
    const collectionListStoreMock = singleMediaSelectionOverlay.instance().collectionListStore;

    expect(mediaListStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).not.toHaveBeenCalled();
    singleMediaSelectionOverlay.unmount();
    expect(mediaListStoreMock.destroy).toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).toHaveBeenCalled();
});
