// @flow

import {mount, shallow} from 'enzyme';
import {observable, toJS} from 'mobx';
import pretty from 'pretty';
import React from 'react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaSelectionOverlay from '../../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Form: require('sulu-admin-bundle/containers/Form').default,
        FormStore: jest.fn(),
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey, userSettingsKey, observableOptions) {
            const {extendObservable} = jest.requireActual('mobx');
            const COLLECTIONS_RESOURCE_KEY = 'collections';

            const collectionData = [
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
                },
            ];

            const thumbnails = {
                'sulu-240x': 'http://lorempixel.com/240/100',
                'sulu-25x25': 'http://lorempixel.com/25/25',
            };

            const mediaData = [
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
            ];
            extendObservable(this, {
                selections: [],
                selectionIds: [],
            });
            this.userSettingsKey = userSettingsKey;
            this.observableOptions = observableOptions;
            this.loading = false;
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
            this.setLimit = jest.fn();
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.getPage = jest.fn().mockReturnValue(2);
            this.getFields = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.updateLoadingStrategy = jest.fn();
            this.updateStructureStrategy = jest.fn();
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.clear = jest.fn();
            this.setAppendRequestData = jest.fn();
            this.deselectEntirePage = jest.fn();
            this.select = jest.fn();
            this.getSchema = jest.fn().mockReturnValue({});
        }),
        FlatStructureStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/structureStrategies/FlatStructureStrategy'
        ).default,
        InfiniteLoadingStrategy: require(
            'sulu-admin-bundle/containers/Datagrid/loadingStrategies/InfiniteLoadingStrategy'
        ).default,
        SingleDatagridOverlay: jest.fn(() => null),
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    return {
        add: jest.fn(),
        getOptions: jest.fn().mockReturnValue({}),
        has: jest.fn(),
        get: jest.fn((key) => {
            const adapters = {
                'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
                'media_card_selection': require('../../Datagrid/adapters/MediaCardSelectionAdapter').default,
            };
            return adapters[key];
        }),
    };
});

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function() {
        this.destroy = jest.fn();
        this.loading = false;
        this.id = 1;
        this.data = {
            id: 1,
        };
    }),
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
        }
    },
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.reset_selection':
                return 'Reset fields';
            case 'sulu_media.select_media_plural':
                return 'Select media';
            case 'sulu_admin.confirm':
                return 'Confirm';
        }
    },
}));

jest.mock('sulu-admin-bundle/containers/SingleDatagridOverlay', () => jest.fn(() => null));

test('Render an open MediaSelectionOverlay', () => {
    const locale = observable.box();
    mount(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).render();

    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('Should instantiate the needed stores when the overlay opens', () => {
    const mediaResourceKey = 'media';
    const collectionResourceKey = 'collections';
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).instance();

    expect(mediaSelectionOverlayInstance.mediaPage.get()).toBe(1);
    expect(mediaSelectionOverlayInstance.collectionPage.get()).toBe(1);

    // $FlowFixMe
    expect(DatagridStore.mock.calls[1][0]).toBe(mediaResourceKey);
    // $FlowFixMe
    expect(DatagridStore.mock.calls[1][1]).toBe('media_selection_overlay');
    // $FlowFixMe
    expect(DatagridStore.mock.calls[1][2].locale).toBe(locale);
    // $FlowFixMe
    expect(DatagridStore.mock.calls[1][2].page.get()).toBe(1);
    // $FlowFixMe
    expect(DatagridStore.mock.calls[1][3].fields).toEqual([
        'id',
        'type',
        'name',
        'size',
        'title',
        'mimeType',
        'subVersion',
        'thumbnails',
    ].join(','));

    // $FlowFixMe
    expect(DatagridStore.mock.calls[0][0]).toBe(collectionResourceKey);
    // $FlowFixMe
    expect(DatagridStore.mock.calls[0][1]).toBe('media_selection_overlay');
    // $FlowFixMe
    expect(DatagridStore.mock.calls[0][2].locale).toBe(locale);
    // $FlowFixMe
    expect(DatagridStore.mock.calls[0][2].page.get()).toBe(1);
    // $FlowFixMe
    expect(DatagridStore.mock.calls[0][2].parentId.get()).toBe(undefined);
});

test('Should call onConfirm callback with selections from datagrid', () => {
    const confirmSpy = jest.fn();
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
        />
    );

    const selections = [
        {id: 1},
        {id: 3},
    ];
    mediaSelectionOverlay.instance().mediaDatagridStore.selections = selections;
    mediaSelectionOverlay.find('Overlay').simulate('confirm');

    expect(toJS(confirmSpy.mock.calls[0][0])).toEqual(selections);
});

test('Should reset the selection array when the "Reset Selection" button was clicked', () => {
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).instance();

    mediaSelectionOverlayInstance.handleSelectionReset();
    expect(mediaSelectionOverlayInstance.mediaDatagridStore.clearSelection).toBeCalled();
});

test('Should destroy the stores and cleanup all states when the overlay is closed', () => {
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    ).instance();

    mediaSelectionOverlayInstance.collectionId.set(1);

    expect(mediaSelectionOverlayInstance.collectionId.get()).toBe(1);

    mediaSelectionOverlayInstance.handleClose();
    expect(mediaSelectionOverlayInstance.collectionId.get()).toBe(undefined);
    expect(mediaSelectionOverlayInstance.collectionStore.resourceStore.destroy).toBeCalled();
    expect(mediaSelectionOverlayInstance.mediaDatagridStore.destroy).toBeCalled();
    expect(mediaSelectionOverlayInstance.collectionDatagridStore.destroy).toBeCalled();
});

test('Should change collection with selected media', () => {
    const locale = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    mediaSelectionOverlay.instance().mediaPage.set(4);
    mediaSelectionOverlay.instance().collectionPage.set(3);

    mediaSelectionOverlay.find('Folder').at(0).simulate('click');

    expect(mediaSelectionOverlay.instance().mediaPage.get()).toEqual(1);
    expect(mediaSelectionOverlay.instance().collectionPage.get()).toEqual(1);
    expect(mediaSelectionOverlay.instance().collectionId.get()).toEqual(1);
    expect(mediaSelectionOverlay.instance().mediaDatagridStore.clearSelection).not.toBeCalled();
});

test('Should reset both datagrid to first page after reopening overlay', () => {
    const locale = observable.box();
    const mediaSelectionOverlay = mount(
        <MediaSelectionOverlay
            excludedIds={[]}
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
        />
    );

    mediaSelectionOverlay.instance().mediaPage.set(3);
    mediaSelectionOverlay.instance().collectionPage.set(2);

    mediaSelectionOverlay.setProps({open: false});
    mediaSelectionOverlay.setProps({open: true});

    expect(mediaSelectionOverlay.instance().mediaPage.get()).toEqual(1);
    expect(mediaSelectionOverlay.instance().collectionPage.get()).toEqual(1);
});
