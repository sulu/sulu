// @flow
/* eslint-disable react/jsx-no-bind */
import {observable} from 'mobx';
import React from 'react';
import {render, screen} from '@testing-library/react';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import {Overlay as OverlayComponent} from 'sulu-admin-bundle/components';
import MediaCollectionComponent from '../../MediaCollection';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Overlay: jest.fn(function Overlay(props) {
        function handleResetClick() {
            props.actions[0].onClick();
        }

        function handleConfirmClick() {
            props.onConfirm();
        }

        return (
            <div>
                <button onClick={handleResetClick} type="button">
                    reset
                </button>
                <button onClick={handleConfirmClick} type="button">
                    confirm
                </button>
                {props.children}
            </div>
        );
    }),
}));

jest.mock('../../MediaCollection', () => jest.fn(function MediaCollection(props) {
    function handleNavigate() {
        props.onCollectionNavigate(1);
    }

    return (
        <button onClick={handleNavigate} type="button">
            navigate-collection
        </button>
    );
}));

jest.mock('../../../stores/CollectionStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.id = 1;
    this.loading = false;
    this.permissions = {};
    this.resourceStore = {};
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () =>
    jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
        this.observableOptions = observableOptions;
        this.data = [];
        this.selections = [];
        this.selectionIds = [];
        this.clearSelection = jest.fn();
        this.reload = jest.fn();
        this.clear = jest.fn();
        this.getSchema = jest.fn().mockReturnValue({});
        this.options = {};
        this.setPage = jest.fn();
    })
);

let collectionListStoreMock: ListStore;
let mediaListStoreMock: ListStore;

function getLatestOverlayProps() {
    const calls = (OverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();

    collectionListStoreMock = new ListStore('collections', 'collections', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    collectionListStoreMock.data.push(
        {
            id: 1,
            title: 'Title 1',
            objectCount: 1,
            description: 'Description 1',
        },
        {
            id: 2,
            title: 'Title 2',
            objectCount: 0,
            description: 'Description 2',
        }
    );

    mediaListStoreMock = new ListStore('media', 'media', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    mediaListStoreMock.data.push(
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        },
        {
            id: 2,
            title: 'Title 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            },
        }
    );
});

test('Render an open MediaSelectionOverlay', () => {
    const locale = observable.box();
    const {asFragment} = render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render an open MediaSelectionOverlay with selected items', () => {
    mediaListStoreMock.selections.push({id: 1});

    const locale = observable.box();
    const {asFragment} = render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render the overlay with a loading confirm button', () => {
    render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            confirmLoading={true}
            locale={observable.box()}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(getLatestOverlayProps().confirmLoading).toEqual(true);
});

test('Should call onConfirm callback with selected medias from media list', () => {
    const confirmSpy = jest.fn();
    const locale = observable.box();
    render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const selections = [
        {id: 1},
        {id: 3},
    ];
    mediaListStoreMock.selections = selections;
    screen.getByRole('button', {name: 'confirm'}).click();

    expect(confirmSpy).toBeCalledWith(selections);
});

test('Should reset the selection of the media list when the reset-button is clicked', () => {
    const locale = observable.box();
    render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    screen.getByRole('button', {name: 'reset'}).click();
    expect(mediaListStoreMock.clearSelection).toBeCalled();
});

test('Should reset the selection of the media list when the overlay is closed', () => {
    const locale = observable.box();
    const {rerender} = render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    rerender(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
        />
    );
    expect(mediaListStoreMock.clearSelection).toBeCalled();
});

test('Should change the current collection id and reset the page of the lists on collection-change', () => {
    const locale = observable.box();
    const collectionId = observable.box();
    render(
        <MediaSelectionOverlay
            collectionId={collectionId}
            collectionListStore={collectionListStoreMock}
            locale={locale}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(collectionListStoreMock.setPage).not.toHaveBeenCalled();
    expect(mediaListStoreMock.setPage).not.toHaveBeenCalled();

    screen.getByRole('button', {name: 'navigate-collection'}).click();

    expect(collectionListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(mediaListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(collectionId.get()).toEqual(1);
    expect(mediaListStoreMock.clearSelection).not.toBeCalled();
    expect(MediaCollectionComponent).toHaveBeenCalled();
});
