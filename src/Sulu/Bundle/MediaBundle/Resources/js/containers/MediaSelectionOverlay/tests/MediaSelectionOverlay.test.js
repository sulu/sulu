// @flow
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ListStore from 'sulu-admin-bundle/containers/List/stores/ListStore';
import MediaCollection from '../../MediaCollection';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';
import type {IObservableValue} from 'mobx/lib/mobx';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.id = 1;

    mockExtendObservable(this, {
        data: {
            id: 1,
            _permissions: {},
        },
    });
}));

jest.mock('sulu-admin-bundle/containers/List/registries/listAdapterRegistry', () => {
    return {
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn().mockReturnValue(true),
        get: jest.fn((key) => {
            const adapters = {
                'folder': require('sulu-admin-bundle/containers/List/adapters/FolderAdapter').default,
                'media_card_selection': require('../../List/adapters/MediaCardSelectionAdapter').default,
                'table': require('sulu-admin-bundle/containers/List/adapters/TableAdapter').default,
            };
            return adapters[key];
        }),
    };
});

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () =>
    jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
        mockExtendObservable(this, {
            selections: [],
            selectionIds: [],
        });
        this.observableOptions = observableOptions;
        this.pageCount = 3;
        this.filterOptions = {
            get: jest.fn().mockReturnValue({}),
        };
        this.active = {
            get: jest.fn(),
        };
        this.sortColumn = {
            get: jest.fn(),
        };
        this.sortOrder = {
            get: jest.fn(),
        };
        this.searchTerm = {
            get: jest.fn(),
        };
        this.limit = {
            get: jest.fn().mockReturnValue(10),
        };
        this.data = [];
        this.getPage = jest.fn().mockReturnValue(2);
        this.setPage = jest.fn();

        this.updateLoadingStrategy = jest.fn();
        this.updateStructureStrategy = jest.fn();
        this.clearSelection = jest.fn();
        this.reload = jest.fn();
        this.clear = jest.fn();
        this.destroy = jest.fn();
        this.getSchema = jest.fn().mockReturnValue({});
        this.options = {};
    })
);

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function() {
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(),
}));

jest.mock('../../MediaCollection', () => {
    const React = require('react');

    class MediaCollectionMock extends React.Component<*> {
        handleCollectionNavigate = () => {
            this.props.onCollectionNavigate(1);
        };

        render() {
            return (
                <button
                    onClick={this.handleCollectionNavigate}
                    type="button"
                >
                    navigate collection
                </button>
            );
        }
    }

    return jest.fn(function(props) {
        return <MediaCollectionMock onCollectionNavigate={props.onCollectionNavigate} />;
    });
});

type RenderOptions = {|
    collectionId?: IObservableValue<?string | number>,
    confirmLoading?: boolean,
    locale?: IObservableValue<string>,
    onClose?: () => void,
    onConfirm?: (selectedMedia: Array<Object>) => void,
    open?: boolean,
|};

let collectionListStoreMock: ListStore;
let mediaListStoreMock: ListStore;

const MediaCollectionMock = (MediaCollection: any);

beforeEach(() => {
    jest.clearAllMocks();

    collectionListStoreMock = new ListStore('collections', 'collections', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    collectionListStoreMock.data.push({
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
    });

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

function renderMediaSelectionOverlay(options?: RenderOptions) {
    return render(
        <MediaSelectionOverlay
            collectionId={options && options.collectionId ? options.collectionId : observable.box()}
            collectionListStore={collectionListStoreMock}
            confirmLoading={options && options.confirmLoading !== undefined ? options.confirmLoading : false}
            locale={options && options.locale ? options.locale : observable.box('en')}
            mediaListStore={mediaListStoreMock}
            onClose={options && options.onClose ? options.onClose : jest.fn()}
            onConfirm={options && options.onConfirm ? options.onConfirm : jest.fn()}
            open={options && options.open !== undefined ? options.open : true}
        />
    );
}

function getConfirmButton() {
    return screen.getByRole('button', {name: 'sulu_admin.confirm'});
}

function expectLoaderInConfirmButton() {
    expect(within(getConfirmButton()).getByText((content, element) => (
        !!element && element.classList.contains('spinner')
    ))).toBeInTheDocument();
}

test('Render an open MediaSelectionOverlay', () => {
    renderMediaSelectionOverlay();

    expect(screen.getByText('sulu_media.select_media_plural')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_media.reset_selection'})).toBeInTheDocument();
    expect(getConfirmButton()).toBeDisabled();
    expect(screen.getByRole('button', {name: 'navigate collection'})).toBeInTheDocument();
    expect(MediaCollectionMock).toHaveBeenCalledWith(expect.objectContaining({
        mediaListAdapters: ['media_card_selection', 'table'],
        overlayType: 'dialog',
        uploadOverlayOpen: false,
    }), expect.anything());
});

test('Render an open MediaSelectionOverlay with selected items', () => {
    mediaListStoreMock.selections.push({id: 1});

    renderMediaSelectionOverlay();

    expect(screen.getByText('sulu_media.select_media_plural')).toBeInTheDocument();
    expect(getConfirmButton()).toBeEnabled();
});

test('Render the overlay with a loading confirm button', () => {
    mediaListStoreMock.selections.push({id: 1});

    renderMediaSelectionOverlay({confirmLoading: true});

    expect(getConfirmButton()).toBeDisabled();
    expectLoaderInConfirmButton();
});

test('Should call onConfirm callback with selected medias from media list', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    const selections = [
        {id: 1},
        {id: 3},
    ];
    mediaListStoreMock.selections = selections;

    renderMediaSelectionOverlay({onConfirm: confirmSpy});

    await user.click(getConfirmButton());

    expect(confirmSpy).toHaveBeenCalledWith(selections);
});

test('Should reset the selection of the media list when the reset-button is clicked', async() => {
    const user = userEvent.setup();
    renderMediaSelectionOverlay();

    await user.click(screen.getByRole('button', {name: 'sulu_media.reset_selection'}));
    expect(mediaListStoreMock.clearSelection).toHaveBeenCalled();
});

test('Should reset the selection of the media list when the overlay is closed', () => {
    const locale = observable.box('en');
    const {rerender} = renderMediaSelectionOverlay();

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
    expect(mediaListStoreMock.clearSelection).toHaveBeenCalled();
});

test('Should change the current collection id and reset the page of the lists on collection-change', async() => {
    const user = userEvent.setup();
    const collectionId = observable.box();
    renderMediaSelectionOverlay({collectionId});

    expect(collectionListStoreMock.setPage).not.toHaveBeenCalled();
    expect(mediaListStoreMock.setPage).not.toHaveBeenCalled();

    await user.click(screen.getByRole('button', {name: 'navigate collection'}));

    expect(collectionListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(mediaListStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(collectionId.get()).toEqual(1);
    expect(mediaListStoreMock.clearSelection).not.toHaveBeenCalled();
});
