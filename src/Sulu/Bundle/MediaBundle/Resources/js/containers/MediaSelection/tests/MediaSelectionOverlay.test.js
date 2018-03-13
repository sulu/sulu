/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, shallow} from 'enzyme';
import {observable, toJS} from 'mobx';
import pretty from 'pretty';
import React from 'react';
import datagridAdapterRegistry from 'sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaCardSelectionAdapter from '../../Datagrid/adapters/MediaCardSelectionAdapter';
import MediaSelectionOverlay from '../MediaSelectionOverlay';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        Form: require('sulu-admin-bundle/containers/Form').default,
        FormStore: jest.fn(),
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey) {
            const {extendObservable} = require.requireActual('mobx');
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
            this.loading = false;
            this.pageCount = 3;
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.getPage = jest.fn().mockReturnValue(2);
            this.getFields = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.updateStrategies = jest.fn();
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.clearData = jest.fn();
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
    };
});

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        has: jest.fn(),
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
            case 'sulu_media.select_media':
                return 'Select media';
            case 'sulu_admin.confirm':
                return 'Confirm';
        }
    },
}));

beforeEach(() => {
    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
        'media_card_selection': MediaCardSelectionAdapter,
    });
});

test('Render an open MediaSelectionOverlay', () => {
    const locale = observable.box();
    const body = document.body;
    mount(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).render();

    expect(pretty(body.innerHTML)).toMatchSnapshot();
});

test('Should instantiate the needed stores when the overlay opens', () => {
    const mediaResourceKey = 'media';
    const collectionResourceKey = 'collections';
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).instance();

    expect(mediaSelectionOverlayInstance.mediaPage.get()).toBe(1);
    expect(mediaSelectionOverlayInstance.collectionPage.get()).toBe(1);

    expect(DatagridStore.mock.calls[1][0]).toBe(mediaResourceKey);
    expect(DatagridStore.mock.calls[1][1].locale).toBe(locale);
    expect(DatagridStore.mock.calls[1][1].page.get()).toBe(1);
    expect(DatagridStore.mock.calls[1][2].fields).toEqual([
        'id',
        'type',
        'name',
        'size',
        'title',
        'mimeType',
        'subVersion',
        'thumbnails',
    ].join(','));

    expect(DatagridStore.mock.calls[0][0]).toBe(collectionResourceKey);
    expect(DatagridStore.mock.calls[0][1].locale).toBe(locale);
    expect(DatagridStore.mock.calls[0][1].page.get()).toBe(1);
});

test('Should call onConfirm callback with selections from datagrid', () => {
    const confirmSpy = jest.fn();
    const locale = observable.box();
    const mediaSelectionOverlay = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
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
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    ).instance();

    mediaSelectionOverlayInstance.handleSelectionReset();
    expect(mediaSelectionOverlayInstance.mediaDatagridStore.clearSelection).toBeCalled();
});

test('Should destroy the stores and cleanup all states when the overlay is closed', () => {
    const locale = observable.box();
    const mediaSelectionOverlayInstance = shallow(
        <MediaSelectionOverlay
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
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
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
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
            open={true}
            locale={locale}
            excludedIds={[]}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
        />
    );

    mediaSelectionOverlay.instance().mediaPage.set(3);
    mediaSelectionOverlay.instance().collectionPage.set(2);

    mediaSelectionOverlay.setProps({open: false});
    mediaSelectionOverlay.setProps({open: true});

    expect(mediaSelectionOverlay.instance().mediaPage.get()).toEqual(1);
    expect(mediaSelectionOverlay.instance().collectionPage.get()).toEqual(1);
});
