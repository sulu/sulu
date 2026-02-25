// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Overlay = jest.fn(function OverlayMock({children, onConfirm}) {
        return React.createElement(
            'div',
            {'data-testid': 'overlay'},
            children,
            React.createElement('button', {onClick: onConfirm, type: 'button'}, 'confirm')
        );
    });

    return {Overlay};
});

jest.mock('../../MediaCollection', () => {
    const React = require('react');

    return jest.fn(function MediaCollectionMock({onCollectionNavigate}) {
        function handleNavigate() {
            onCollectionNavigate(1);
        }

        return React.createElement(
            'button',
            {'data-testid': 'navigate-collection', onClick: handleNavigate, type: 'button'},
            'navigate'
        );
    });
});

jest.mock('../../../stores/CollectionStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

const overlayComponent = ((jest.requireMock('sulu-admin-bundle/components'): any): {
    Overlay: {mock: {calls: Array<[Object]>}},
    ...
});

function createListStore() {
    return {
        clear: jest.fn(),
        clearSelection: jest.fn(),
        data: [],
        selections: [],
        setPage: jest.fn(),
    };
}

function getLatestOverlayProps(): any {
    return getLatestMockProps(overlayComponent.Overlay);
}

let collectionListStoreMock: any;
let mediaListStoreMock: any;

beforeEach(() => {
    jest.clearAllMocks();

    collectionListStoreMock = createListStore();
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

    mediaListStoreMock = createListStore();
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

test('Should call onConfirm callback with selected medias from media list', async() => {
    const confirmSpy = jest.fn();
    const user = userEvent.setup();
    mediaListStoreMock.selections = [{id: 1}, {id: 3}];

    render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={observable.box()}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    await user.click(screen.getByRole('button', {name: 'confirm'}));
    expect(confirmSpy).toHaveBeenCalledWith(mediaListStoreMock.selections);
});

test('Should reset the selection of the media list when the reset-button is clicked', () => {
    render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={observable.box()}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    getLatestOverlayProps().actions[0].onClick();
    expect(mediaListStoreMock.clearSelection).toHaveBeenCalled();
});

test('Should reset the selection of the media list when the overlay is closed', () => {
    const {rerender} = render(
        <MediaSelectionOverlay
            collectionId={observable.box()}
            collectionListStore={collectionListStoreMock}
            locale={observable.box()}
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
            locale={observable.box()}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
        />
    );

    expect(mediaListStoreMock.clearSelection).toHaveBeenCalled();
});

test('Should change the current collection id and reset the page of the lists on collection-change', async() => {
    const user = userEvent.setup();
    const collectionId = observable.box();

    render(
        <MediaSelectionOverlay
            collectionId={collectionId}
            collectionListStore={collectionListStoreMock}
            locale={observable.box()}
            mediaListStore={mediaListStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(collectionListStoreMock.setPage).not.toHaveBeenCalled();
    expect(mediaListStoreMock.setPage).not.toHaveBeenCalled();

    await user.click(screen.getByTestId('navigate-collection'));

    expect(collectionListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(mediaListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(collectionId.get()).toEqual(1);
    expect(mediaListStoreMock.clearSelection).not.toHaveBeenCalled();
});
