// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import MultiMediaSelection from '../MultiMediaSelection';
import MultiMediaSelectionStore from '../../../stores/MultiMediaSelectionStore';
import MultiMediaSelectionOverlay from '../../MultiMediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../MultiMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

jest.mock('../../../stores/MultiMediaSelectionStore', () => jest.fn(function() {
    this.selectedMedia = [];
    this.selectedMediaIds = [];
    this.loadSelectedMedia = jest.fn();
}));

test('Render a MultiMediaSelection field', () => {
    // $FlowFixMe
    MultiMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
        this.selectedMediaIds = [1, 2, 3];
    });

    expect(render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    )).toMatchSnapshot();
});

test('The MultiMediaSelection should have 3 child-items', () => {
    // $FlowFixMe
    MultiMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 2,
                title: 'Media 2',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
            {
                id: 3,
                title: 'Media 3',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
        this.selectedMediaIds = [1, 2, 3];
    });

    const mediaSelection = shallow(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(mediaSelection.find('Item').length).toBe(3);
});

test('Clicking on the "add media" button should open up an overlay', () => {
    const mediaSelection = mount(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    expect(mediaSelection.find(MultiMediaSelectionOverlay).prop('open')).toEqual(false);
    mediaSelection.find('.button.left').simulate('click');
    expect(mediaSelection.find(MultiMediaSelectionOverlay).prop('open')).toEqual(true);
});

test('Should remove media from the selection store', () => {
    // $FlowFixMe
    MultiMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.removeById = jest.fn();
    });

    const mediaSelectionInstance = shallow(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    ).instance();

    mediaSelectionInstance.handleRemove(1);
    expect(mediaSelectionInstance.mediaSelectionStore.removeById).toBeCalledWith(1);
});

test('Should move media inside the selection store', () => {
    // $FlowFixMe
    MultiMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.move = jest.fn();
    });

    const mediaSelectionInstance = shallow(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    ).instance();

    mediaSelectionInstance.handleSorted(1, 3);
    expect(mediaSelectionInstance.mediaSelectionStore.move).toBeCalledWith(1, 3);
});

test('Should add the selected medias to the selection store on confirm', () => {
    // $FlowFixMe
    MultiMediaSelectionStore.mockImplementationOnce(function() {
        this.selectedMedia = [];
        this.selectedMediaIds = [];
        this.add = jest.fn();
    });

    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const mediaSelectionInstance = shallow(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    ).instance();

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleOverlayConfirm([
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ]);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].id).toBe(1);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[0][0].title).toBe('Title 1');
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].id).toBe(2);
    expect(mediaSelectionInstance.mediaSelectionStore.add.mock.calls[1][0].title).toBe('Title 2');
    expect(mediaSelectionInstance.overlayOpen).toBe(false);
});

test('Should call the onChange handler if selection store changes', () => {
    // $FlowFixMe
    MultiMediaSelectionStore.mockImplementationOnce(function(selectedIds) {
        mockExtendObservable(this, {
            selectedMedia: selectedIds.map((id) => {
                return {id, thumbnails: {}};
            }),
            get selectedMediaIds() {
                return this.selectedMedia.map((media) => media.id);
            },
        });
    });

    const changeSpy = jest.fn();

    const mediaSelectionInstance = shallow(
        <MultiMediaSelection locale={observable.box('en')} onChange={changeSpy} value={{ids: [55]}} />
    ).instance();

    mediaSelectionInstance.mediaSelectionStore.selectedMedia.push({id: 99, thumbnails: {}});
    expect(changeSpy).toBeCalledWith({ids: [55, 99]});

    mediaSelectionInstance.mediaSelectionStore.selectedMedia.splice(0, 1);
    expect(changeSpy).toBeCalledWith({ids: [99]});
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const mediaSelection = shallow(
        <MultiMediaSelection locale={observable.box('en')} onChange={changeSpy} value={{ids: [55]}} />
    );

    mediaSelection.setProps({disabled: true});
    expect(changeSpy).not.toBeCalled();
});

test('Pass correct props to MultiItemSelection component', () => {
    const mediaSelection = mount(
        <MultiMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(mediaSelection.find('MultiItemSelection').prop('disabled')).toEqual(true);
});
