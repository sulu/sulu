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
    MediaSelectionOverlay.createCollectionDatagridStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });
    MediaSelectionOverlay.createMediaDatagridStore = jest.fn().mockReturnValue({
        destroy: jest.fn(),
    });

    return MediaSelectionOverlay;
});

test('Should create datagrid-stores with correct locale', () => {
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

    expect(MediaSelectionOverlay.createMediaDatagridStore).toHaveBeenCalledWith(
        expect.anything(),
        expect.anything(),
        locale
    );
    expect(MediaSelectionOverlay.createMediaDatagridStore.mock.calls[0][1].get()).toEqual('22,44');
    expect(MediaSelectionOverlay.createCollectionDatagridStore).toHaveBeenCalledWith(expect.anything(), locale);
});

test('Should pass correct props to media-selection-overlay', () => {
    const mediaDatagridStoreMock = jest.fn();
    MediaSelectionOverlay.createMediaDatagridStore.mockReturnValueOnce(mediaDatagridStoreMock);
    const collectionDatagridStoreMock = jest.fn();
    MediaSelectionOverlay.createCollectionDatagridStore.mockReturnValueOnce(collectionDatagridStoreMock);

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

    expect(mediaSelectionOverlay.prop('mediaDatagridStore')).toEqual(mediaDatagridStoreMock);
    expect(mediaSelectionOverlay.prop('collectionDatagridStore')).toEqual(collectionDatagridStoreMock);
    expect(mediaSelectionOverlay.prop('excludedIds')).toEqual([22, 44]);
    expect(mediaSelectionOverlay.prop('locale')).toEqual(locale);
    expect(mediaSelectionOverlay.prop('open')).toEqual(true);
    expect(mediaSelectionOverlay.prop('onClose')).toEqual(onClose);
    expect(mediaSelectionOverlay.prop('onConfirm')).toEqual(onConfirm);
});

test('Should destroy datagrid-stores on unmount', () => {
    const multiMediaSelectionOverlay = shallow(
        <MultiMediaSelectionOverlay
            excludedIds={[]}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    const mediaDatagridStoreMock = multiMediaSelectionOverlay.instance().mediaDatagridStore;
    const collectionDatagridStoreMock = multiMediaSelectionOverlay.instance().collectionDatagridStore;

    expect(mediaDatagridStoreMock.destroy).not.toHaveBeenCalled();
    expect(collectionDatagridStoreMock.destroy).not.toHaveBeenCalled();
    multiMediaSelectionOverlay.unmount();
    expect(mediaDatagridStoreMock.destroy).toHaveBeenCalled();
    expect(collectionDatagridStoreMock.destroy).toHaveBeenCalled();
});
