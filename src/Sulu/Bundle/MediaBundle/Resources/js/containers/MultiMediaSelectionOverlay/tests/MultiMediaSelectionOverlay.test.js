// @flow
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import React from 'react';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const MediaSelectionOverlay = function() {
        return <div>single media selection overlay</div>;
    };
    MediaSelectionOverlay.createCollectionListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaListStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
        clear: jest.fn(),
    });

    return MediaSelectionOverlay;
});

test('Should create list-stores with correct locale', () => {
    const locale = observable.box('en');
    shallow(
        <MultiMediaSelectionOverlay
            excludedIds={[44, 22]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).render();

    expect(MediaSelectionOverlay.createMediaListStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale
    );
    expect(MediaSelectionOverlay.createMediaListStore.mock.calls[0][1].get()).toEqual([44, 22]);
    expect(MediaSelectionOverlay.createCollectionListStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaListStoreMock = {clear: jest.fn()};
    MediaSelectionOverlay.createMediaListStore.mockReturnValueOnce(mediaListStoreMock);
    const collectionListStoreMock = jest.fn();
    MediaSelectionOverlay.createCollectionListStore.mockReturnValueOnce(collectionListStoreMock);

    const locale = observable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();

    const multiMediaSelectionOverlay = shallow(
        <MultiMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );
    const mediaSelectionOverlay = multiMediaSelectionOverlay.find(MediaSelectionOverlay);

    expect(mediaSelectionOverlay.prop('mediaListStore')).toEqual(mediaListStoreMock);
    expect(mediaSelectionOverlay.prop('collectionListStore')).toEqual(collectionListStoreMock);
    expect(mediaSelectionOverlay.prop('locale')).toEqual(locale);
    expect(mediaSelectionOverlay.prop('open')).toEqual(true);
    expect(mediaSelectionOverlay.prop('onClose')).toEqual(onClose);
    expect(mediaSelectionOverlay.prop('onConfirm')).toEqual(onConfirm);
});

test('Should destroy list-stores on unmount', () => {
    const multiMediaSelectionOverlay = shallow(
        <MultiMediaSelectionOverlay
            excludedIds={[]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const mediaListStoreMock = multiMediaSelectionOverlay.instance().mediaListStore;
    const collectionListStoreMock = multiMediaSelectionOverlay.instance().collectionListStore;

    expect(mediaListStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).not.toHaveBeenCalled();
    multiMediaSelectionOverlay.unmount();
    expect(mediaListStoreMock.destroy).toHaveBeenCalled();
    expect(collectionListStoreMock.destroy).toHaveBeenCalled();
});
