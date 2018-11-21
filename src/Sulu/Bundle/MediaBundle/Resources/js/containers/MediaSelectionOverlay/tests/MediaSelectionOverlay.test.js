// @flow
import {mount, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import React from 'react';
import DatagridStore from 'sulu-admin-bundle/containers/Datagrid/stores/DatagridStore';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';
import MediaCollection from '../../MediaCollection/MediaCollection';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.destroy = jest.fn();
    this.id = 1;
    this.data = {
        id: 1,
    };
}));

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    return {
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn().mockReturnValue(true),
        get: jest.fn((key) => {
            const adapters = {
                'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
                'media_card_selection': require('../../Datagrid/adapters/MediaCardSelectionAdapter').default,
            };
            return adapters[key];
        }),
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/stores/DatagridStore', () =>
    jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
        mockExtendObservable(this, {
            selections: [],
            selectionIds: [],
        });
        this.observableOptions = observableOptions;
        this.pageCount = 3;
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
        this.getSchema = jest.fn().mockReturnValue({});
        this.options = {};
    })
);

jest.mock('sulu-admin-bundle/containers/Form/stores/FormStore', () => jest.fn());

let collectionDatagridStoreMock: DatagridStore;
let mediaDatagridStoreMock: DatagridStore;

beforeEach(() => {
    jest.clearAllMocks();

    collectionDatagridStoreMock = new DatagridStore('collections', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    collectionDatagridStoreMock.data.push({
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

    mediaDatagridStoreMock = new DatagridStore('media', 'media_selection_overlay', {
        page: observable.box(),
    }, {});
    mediaDatagridStoreMock.data.push(
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
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={observable.box()}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.render()).toMatchSnapshot();
    expect(mediaSelectionOverlay.find(MediaCollection).closest('Portal').render()).toMatchSnapshot();
});

test('Render an open MediaSelectionOverlay with selected items', () => {
    mediaDatagridStoreMock.selections.push({id: 1});

    const locale = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={observable.box()}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaSelectionOverlay.render()).toMatchSnapshot();
    expect(mediaSelectionOverlay.find(MediaCollection).closest('Portal').render()).toMatchSnapshot();
});

test('Should call onConfirm callback with selected medias from media datagrid', () => {
    const confirmSpy = jest.fn();
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={observable.box()}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const selections = [
        {id: 1},
        {id: 3},
    ];
    mediaDatagridStoreMock.selections = selections;
    mediaSelectionOverlay.find('Overlay').simulate('confirm');

    expect(confirmSpy).toBeCalledWith(selections);
});

test('Should reset the selection of the media datagrid when the reset-button is clicked', () => {
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={observable.box()}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).instance();

    mediaSelectionOverlayInstance.handleSelectionReset();
    expect(mediaDatagridStoreMock.clearSelection).toBeCalled();
});

test('Should reset the selection of the media datagrid when the overlay is closed', () => {
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={observable.box()}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    mediaSelectionOverlay.setProps({open: false});
    expect(mediaDatagridStoreMock.clearSelection).toBeCalled();
});

test('Should change the current collection id and reset the page of the datagrids on collection-change', () => {
    const locale = observable.box();
    const collectionId = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={collectionId}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(collectionDatagridStoreMock.setPage).not.toHaveBeenCalled();
    expect(mediaDatagridStoreMock.setPage).not.toHaveBeenCalled();

    mediaSelectionOverlay.find('Folder').at(0).simulate('click');

    expect(collectionDatagridStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(mediaDatagridStoreMock.setPage).toHaveBeenCalledWith(1);
    expect(collectionId.get()).toEqual(1);
    expect(mediaDatagridStoreMock.clearSelection).not.toBeCalled();
});

test('should update the excluded-option of the media datagrid if the excluded-id prop changes', () => {
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            collectionDatagridStore={collectionDatagridStoreMock}
            collectionId={observable.box()}
            excludedIds={[]}
            locale={locale}
            mediaDatagridStore={mediaDatagridStoreMock}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    expect(mediaDatagridStoreMock.options.excluded).toBeUndefined();
    mediaSelectionOverlay.setProps({excludedIds: [99, 22, 44]});
    expect(mediaDatagridStoreMock.options.excluded).toEqual('22,44,99');
    expect(mediaDatagridStoreMock.reload).toHaveBeenCalled();
});
