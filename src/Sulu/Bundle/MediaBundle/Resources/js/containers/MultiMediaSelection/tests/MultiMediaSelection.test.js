// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import MultiSelectionStore from 'sulu-admin-bundle/stores/MultiSelectionStore';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import MultiMediaSelection from '../MultiMediaSelection';
import MultiMediaSelectionOverlay from '../../MultiMediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../MultiMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

jest.mock('sulu-admin-bundle/stores/MultiSelectionStore', () => jest.fn(function() {
    this.items = [];
    this.loadItems = jest.fn();
}));

const MultiMediaSelectionOverlayMock = (MultiMediaSelectionOverlay: any);

test('Render a MultiMediaSelection field', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
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
    });

    const {container} = render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection
            displayOptions={['top', 'left', 'right', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field with display options and selected icon', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                thumbnails: {
                    'sulu-25x25': 'http://lorempixel.com/25/25',
                },
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection
            displayOptions={['top', 'left', 'right', 'bottom']}
            locale={observable.box('en')}
            onChange={jest.fn()}
            value={{displayOption: 'left', ids: []}}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MultiMediaSelection field without thumbnails with MimeTypeIndicator', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
            {
                id: 1,
                title: 'Media 1',
                mimeType: 'application/json',
            },
            {
                id: 2,
                title: 'Media 2',
                mimeType: 'application/pdf',
            },
            {
                id: 3,
                title: 'Media 3',
                mimeType: 'application/vnd.ms-excel',
            },
        ];
    });

    const {container} = render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(container).toMatchSnapshot();
});

test('The MultiMediaSelection should have 3 child-items', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [
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
    });

    render(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    expect(screen.getAllByRole('button', {name: /Media/})).toHaveLength(3);
});

test('Clicking on the "add media" button should open up an overlay', async() => {
    const user = userEvent.setup();
    render(<MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />);

    expect(MultiMediaSelectionOverlayMock.mock.calls[0][0].open).toEqual(false);
    await user.click(screen.getByRole('button', {name: 'su-image'}));
    expect(MultiMediaSelectionOverlayMock.mock.calls[MultiMediaSelectionOverlayMock.mock.calls.length - 1][0].open)
        .toEqual(true);
});

test('Should remove media from the selection store', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [];
        this.removeById = jest.fn();
    });

    const {instance: mediaSelectionInstance} = renderWithRef(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    mediaSelectionInstance.handleRemove(1);
    expect(mediaSelectionInstance.mediaSelectionStore.removeById).toHaveBeenCalledWith(1);
});

test('Should move media inside the selection store', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [];
        this.move = jest.fn();
    });

    const {instance: mediaSelectionInstance} = renderWithRef(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    mediaSelectionInstance.handleSorted(1, 3);
    expect(mediaSelectionInstance.mediaSelectionStore.move).toHaveBeenCalledWith(1, 3);
});

test('Should add the selected medias to the selection store on confirm', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function() {
        this.items = [];
        this.set = jest.fn();
    });

    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-25x25': 'http://lorempixel.com/25/25',
    };
    const medias = [
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
    ];

    const {instance: mediaSelectionInstance} = renderWithRef(
        <MultiMediaSelection locale={observable.box('en')} onChange={jest.fn()} />
    );

    mediaSelectionInstance.openMediaOverlay();
    mediaSelectionInstance.handleOverlayConfirm(medias);
    expect(mediaSelectionInstance.mediaSelectionStore.set).toHaveBeenCalledWith(medias);
    expect(mediaSelectionInstance.overlayOpen).toBe(false);
});

test('Should call the onChange handler if selection store changes', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}};
            }),
        });
    });

    const changeSpy = jest.fn();

    const {instance: mediaSelectionInstance} = renderWithRef(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    mediaSelectionInstance.mediaSelectionStore.items.push({id: 99, mimeType: 'image/jpeg', thumbnails: {}});
    expect(changeSpy).toHaveBeenCalledWith({ids: [55, 99]});

    mediaSelectionInstance.mediaSelectionStore.items.splice(0, 1);
    expect(changeSpy).toHaveBeenCalledWith({ids: [99]});
});

test('Should call the onChange handler if the displayOption changes', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();

    render(
        <MultiMediaSelection
            displayOptions={['left']}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-display-default su-angle-down'}));
    await user.click(screen.getByRole('button', {name: /sulu_media\.left/}));

    expect(changeSpy).toHaveBeenCalledWith({displayOption: 'left', ids: [55]});
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const {rerender} = renderWithRef(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    rerender(
        <MultiMediaSelection
            disabled={true}
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );
    expect(changeSpy).not.toHaveBeenCalled();
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function(resourceKey, selectedIds) {
        this.loadItems = jest.fn();
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}};
            }),
        });
    });

    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    const {instance: mediaSelectionInstance} = renderWithRef(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={changeSpy}
            value={{displayOption: undefined, ids: [55]}}
        />
    );

    // change callback should be called when item of the store mock changes
    mediaSelectionInstance.mediaSelectionStore.items.push({id: 99, mimeType: 'image/jpeg', thumbnails: {}});
    expect(changeSpy).toHaveBeenCalledWith({ids: [55, 99]});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should call the onItemClick handler if an item is clicked', async() => {
    const user = userEvent.setup();
    // $FlowFixMe
    MultiSelectionStore.mockImplementationOnce(function(resourceKey, selectedIds) {
        mockExtendObservable(this, {
            items: selectedIds.map((id) => {
                return {id, mimeType: 'image/jpeg', thumbnails: {}, title: `Media ${selectedIds.indexOf(id) + 1}`};
            }),
        });
    });

    const itemClickSpy = jest.fn();

    render(
        <MultiMediaSelection
            locale={observable.box('en')}
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            value={{displayOption: undefined, ids: [55, 99]}}
        />
    );

    await user.click(screen.getByText('Media 1'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(55, expect.objectContaining({id: 55, mimeType: 'image/jpeg'}));

    await user.click(screen.getByText('Media 2'));
    expect(itemClickSpy).toHaveBeenLastCalledWith(99, expect.objectContaining({id: 99, mimeType: 'image/jpeg'}));
});

test('Pass correct props to MultiItemSelection component', () => {
    const {instance: mediaSelection} = renderWithRef(
        <MultiMediaSelection disabled={true} locale={observable.box('en')} onChange={jest.fn()} sortable={false} />
    );
    const multiItemSelectionProps = findElementByType(mediaSelection.render(), MultiItemSelection).props;

    expect(multiItemSelectionProps.disabled).toEqual(true);
    expect(multiItemSelectionProps.sortable).toEqual(false);
});
