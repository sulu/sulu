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
    MediaSelectionOverlay.createCollectionDatagridStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaDatagridStore = jest.fn().mockReturnValue({
        selections: mockObservable([]),
        select: jest.fn(),
        clearSelection: jest.fn(),
        destroy: jest.fn(),
    });

    return MediaSelectionOverlay;
});

test('Should create datagrid-stores with correct locale', () => {
    const locale = mockObservable.box('en');
    shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).render();

    expect(MediaSelectionOverlay.createMediaDatagridStore).toHaveBeenCalledWith(expect.anything(), locale);
    expect(MediaSelectionOverlay.createCollectionDatagridStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should update selections of media-datagrid-store to only contain a single item', () => {
    const singleMediaSelectionOverlay = shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={mockObservable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    singleMediaSelectionOverlay.instance().mediaDatagridStore.selections.push({id: 3});
    expect(singleMediaSelectionOverlay.instance().mediaDatagridStore.selections).toEqual([{id: 3}]);

    singleMediaSelectionOverlay.instance().mediaDatagridStore.selections.push({id: 5});
    expect(singleMediaSelectionOverlay.instance().mediaDatagridStore.clearSelection).toBeCalledWith();
    expect(singleMediaSelectionOverlay.instance().mediaDatagridStore.select).toBeCalledWith({id: 5});
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaDatagridStoreMock = { selections: mockObservable([]) };
    MediaSelectionOverlay.createMediaDatagridStore.mockReturnValueOnce(mediaDatagridStoreMock);
    const collectionDatagridStoreMock = jest.fn();
    MediaSelectionOverlay.createCollectionDatagridStore.mockReturnValueOnce(collectionDatagridStoreMock);

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

    expect(mediaSelectionOverlay.prop('mediaDatagridStore')).toEqual(mediaDatagridStoreMock);
    expect(mediaSelectionOverlay.prop('collectionDatagridStore')).toEqual(collectionDatagridStoreMock);
    expect(mediaSelectionOverlay.prop('excludedIds')).toEqual([22, 44]);
    expect(mediaSelectionOverlay.prop('locale')).toEqual(locale);
    expect(mediaSelectionOverlay.prop('open')).toEqual(true);
    expect(mediaSelectionOverlay.prop('onClose')).toEqual(onClose);
});

test('Should destroy datagrid-stores on unmount', () => {
    const singleMediaSelectionOverlay = shallow(
        <SingleMediaSelectionOverlay
            excludedIds={[]}
            locale={mockObservable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const mediaDatagridStoreMock = singleMediaSelectionOverlay.instance().mediaDatagridStore;
    const collectionDatagridStoreMock = singleMediaSelectionOverlay.instance().collectionDatagridStore;

    expect(mediaDatagridStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionDatagridStoreMock.destroy).not.toHaveBeenCalled();
    singleMediaSelectionOverlay.unmount();
    expect(mediaDatagridStoreMock.destroy).toHaveBeenCalled();
    expect(collectionDatagridStoreMock.destroy).toHaveBeenCalled();
});
