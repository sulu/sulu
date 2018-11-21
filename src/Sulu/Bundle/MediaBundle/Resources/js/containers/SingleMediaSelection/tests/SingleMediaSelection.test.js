// @flow
import {mount, shallow} from 'enzyme';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import SingleMediaSelection from '../SingleMediaSelection';
import SingleMediaSelectionStore from '../../../stores/SingleMediaSelectionStore';
import SingleMediaSelectionOverlay from '../../SingleMediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../SingleMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

jest.mock('../../../stores/SingleMediaSelectionStore', () => jest.fn());

test('Component should render without selected media', () => {
    const singleMediaSelection = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(SingleMediaSelectionStore).toBeCalledWith(undefined, expect.anything());
    expect(singleMediaSelection.render()).toMatchSnapshot();
});

test('Component should render with selected media', () => {
    // $FlowFixMe
    SingleMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = {
            id: 33,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnail: '/images/25x25/awesome.png',
        };
        this.selectedMediaId = 33;
    });

    const singleMediaSelection = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={33} />
    );

    expect(SingleMediaSelectionStore).toBeCalledWith(33, expect.anything());
    expect(singleMediaSelection.render()).toMatchSnapshot();
});

test('Click on media-button should open an overlay', () => {
    const singleMediaSelection = mount(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(singleMediaSelection.find(SingleMediaSelectionOverlay).prop('open')).toEqual(false);
    singleMediaSelection.find('.button').simulate('click');
    expect(singleMediaSelection.find(SingleMediaSelectionOverlay).prop('open')).toEqual(true);
});

test('Click on remove-button should clear the selection store', () => {
    // $FlowFixMe
    SingleMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = {
            id: 33,
            title: 'test media',
            mimeType: 'image/jpeg',
            thumbnail: '/images/25x25/awesome.png',
        };
        this.selectedMediaId = 33;
        this.clear = jest.fn();
    });

    const singleMediaSelection = mount(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={33} />
    );

    singleMediaSelection.find('.removeButton').simulate('click');
    expect(singleMediaSelection.instance().singleMediaSelectionStore.clear).toBeCalled();
});

test('Media that is selected in the overlay should be set to the selection store on confirm', () => {
    // $FlowFixMe
    SingleMediaSelectionStore.mockImplementationOnce(function() {
        this.set = jest.fn();
    });

    const singleMediaSelection = mount(
        <SingleMediaSelection locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    singleMediaSelection.instance().handleOverlayConfirm({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    });

    expect(singleMediaSelection.instance().singleMediaSelectionStore.set).toBeCalledWith(expect.objectContaining({
        id: 22,
        title: 'test media',
        mimeType: 'image/jpeg',
        thumbnails: {
            'sulu-25x25': '/images/25x25/awesome.png',
        },
    }));
});

test('onChange-property should be called handler if selection store changes', () => {
    // $FlowFixMe
    SingleMediaSelectionStore.mockImplementationOnce(function() {
        this.loadSelectedMedia = jest.fn();
        mockExtendObservable(this, {
            selectedMedia: undefined,
            get selectedMediaId() {
                return this.selectedMedia ? this.selectedMedia.id : undefined;
            },
        });
    });

    const changeSpy = jest.fn();

    const singleMediaSelectionInstance = shallow(
        <SingleMediaSelection locale={observable.box('en')} onChange={changeSpy} value={undefined} />
    ).instance();

    expect(changeSpy).not.toBeCalled();
    singleMediaSelectionInstance.singleMediaSelectionStore.selectedMedia = {
        id: 77,
        title: 'test media',
        mimeType: 'image/jpeg',
    };
    expect(changeSpy).toBeCalledWith(77);
});

test('Correct props should be passed to SingleItemSelection component', () => {
    const singleMediaSelection = shallow(
        <SingleMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} value={undefined} />
    );

    expect(singleMediaSelection.find(SingleItemSelection).prop('disabled')).toEqual(true);
});
