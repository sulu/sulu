// @flow
import {mount} from 'enzyme';
import {observable} from 'mobx';
import React from 'react';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('../../MediaSelectionOverlay', () => {
    const MediaSelectionOverlay = function() {
        return <div>single media selection overlay</div>;
    };
    MediaSelectionOverlay.createMediaDatagridStore = jest.fn();
    MediaSelectionOverlay.createCollectionDatagridStore = jest.fn();

    return MediaSelectionOverlay;
});

test('Should create datagrid-stores with correct locale', () => {
    const locale = observable.box('en');
    mount(
        <MultiMediaSelectionOverlay
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

test('Should pass correct props to media-selection-overlay', () => {
    const mediaDatagridStoreMock = jest.fn();
    MediaSelectionOverlay.createMediaDatagridStore.mockReturnValue(mediaDatagridStoreMock);
    const collectionDatagridStoreMock = jest.fn();
    MediaSelectionOverlay.createCollectionDatagridStore.mockReturnValue(collectionDatagridStoreMock);

    const locale = observable.box('en');
    const onClose = jest.fn();
    const onConfirm = jest.fn();

    const multiMediaSelectionOverlay = mount(
        <MultiMediaSelectionOverlay
            excludedIds={[22, 44]}
            locale={locale}
            onClose={onClose}
            onConfirm={onConfirm}
            open={true}
        />
    );
    const mediaSelectionOverlay = multiMediaSelectionOverlay.find(MediaSelectionOverlay);

    expect(mediaSelectionOverlay.prop('mediaDatagridStore')).toEqual(mediaDatagridStoreMock);
    expect(mediaSelectionOverlay.prop('collectionDatagridStore')).toEqual(collectionDatagridStoreMock);
    expect(mediaSelectionOverlay.prop('excludedIds')).toEqual([22, 44]);
    expect(mediaSelectionOverlay.prop('locale')).toEqual(locale);
    expect(mediaSelectionOverlay.prop('open')).toEqual(true);
    expect(mediaSelectionOverlay.prop('onClose')).toEqual(onClose);
    expect(mediaSelectionOverlay.prop('onConfirm')).toEqual(onConfirm);
});

test('Should destroy datagrid-stores on unmount', () => {
    const mediaDatagridStoreMock = { destroy: jest.fn() };
    MediaSelectionOverlay.createMediaDatagridStore.mockReturnValue(mediaDatagridStoreMock);

    const collectionDatagridStoreMock = { destroy: jest.fn() };
    MediaSelectionOverlay.createCollectionDatagridStore.mockReturnValue(collectionDatagridStoreMock);

    const multiMediaSelectionOverlay = mount(
        <MultiMediaSelectionOverlay
            excludedIds={[]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaDatagridStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionDatagridStoreMock.destroy).not.toHaveBeenCalled();
    multiMediaSelectionOverlay.unmount();
    expect(mediaDatagridStoreMock.destroy).toHaveBeenCalled();
    expect(collectionDatagridStoreMock.destroy).toHaveBeenCalled();
});
