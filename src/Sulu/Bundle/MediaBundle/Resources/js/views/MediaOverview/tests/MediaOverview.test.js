/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {mount, render} from 'enzyme';
import MediaCardOverviewAdapter from '../../../containers/Datagrid/adapters/MediaCardOverviewAdapter';

jest.mock('sulu-admin-bundle/containers', () => {
    return {
        withToolbar: jest.fn((Component) => Component),
        AbstractAdapter: require('sulu-admin-bundle/containers/Datagrid/adapters/AbstractAdapter').default,
        Datagrid: require('sulu-admin-bundle/containers/Datagrid/Datagrid').default,
        DatagridStore: jest.fn(function(resourceKey) {
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
                'sulu-260x': 'http://lorempixel.com/260/100',
                'sulu-100x100': 'http://lorempixel.com/100/100',
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
                    title: 'Title 1',
                    mimeType: 'image/jpeg',
                    size: 54321,
                    url: 'http://lorempixel.com/500/500',
                    thumbnails: thumbnails,
                },
            ];

            this.loading = false;
            this.pageCount = 3;
            this.data = (resourceKey === COLLECTIONS_RESOURCE_KEY)
                ? collectionData
                : mediaData;
            this.selections = [];
            this.getPage = jest.fn().mockReturnValue(2);
            this.getSchema = jest.fn().mockReturnValue({
                title: {},
                description: {},
            });
            this.destroy = jest.fn();
            this.sendRequest = jest.fn();
            this.clearSelection = jest.fn();
            this.init = jest.fn();
            this.appendRequestData = false;
        }),
    };
});

jest.mock('../../../stores/CollectionStore', () => jest.fn(function() {
    this.parentId = 1;
    this.destroy = jest.fn();
}));

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.all_media':
                return 'All Media';
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Downoad master file';
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

jest.mock('sulu-admin-bundle/services/Translator', () => ({
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

jest.mock('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry', () => {
    const getAllAdaptersMock = jest.fn();

    return {
        getAllAdaptersMock: getAllAdaptersMock,
        add: jest.fn(),
        get: jest.fn((key) => getAllAdaptersMock()[key]),
        has: jest.fn(),
    };
});

beforeEach(() => {
    jest.resetModules();

    const datagridAdapterRegistry = require('sulu-admin-bundle/containers/Datagrid/registries/DatagridAdapterRegistry');

    datagridAdapterRegistry.has.mockReturnValue(true);
    datagridAdapterRegistry.getAllAdaptersMock.mockReturnValue({
        'folder': require('sulu-admin-bundle/containers/Datagrid/adapters/FolderAdapter').default,
        'media_card_overview': MediaCardOverviewAdapter,
    });
});

test('Render a simple MediaOverview', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = {
        bind: jest.fn(),
        attributes: {},
    };

    const mediaOverview = render(<MediaOverview router={router} />);
    expect(mediaOverview).toMatchSnapshot();
});

test('Unbind all query params and destroy all stores on unmount', () => {
    const MediaOverview = require('../MediaOverview').default;
    const router = {
        bind: jest.fn(),
        unbind: jest.fn(),
        attributes: {},
    };

    const mediaOverview = mount(<MediaOverview router={router} />);
    const mediaOverviewInstance = mediaOverview.instance();
    const page = router.bind.mock.calls[0][1];
    const locale = router.bind.mock.calls[1][1];

    expect(page.get()).toBe(undefined);
    expect(locale.get()).toBe(undefined);
    expect(router.bind).toBeCalledWith('collectionPage', page, '1');
    expect(router.bind).toBeCalledWith('locale', locale);

    mediaOverview.unmount();
    expect(mediaOverviewInstance.mediaDatagridStore.destroy).toBeCalled();
    expect(mediaOverviewInstance.collectionDatagridStore.destroy).toBeCalled();
    expect(mediaOverviewInstance.collectionStore.destroy).toBeCalled();
    expect(router.unbind).toBeCalledWith('collectionPage', page);
    expect(router.unbind).toBeCalledWith('locale', locale);
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaOverview = require('../MediaOverview').default;
    const toolbarFunction = withToolbar.mock.calls[0][1];

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['de'],
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaOverview = mount(<MediaOverview router={router} />).get(0);
    mediaOverview.collectionId = 4;
    mediaOverview.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaOverview);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {
        'collectionPage': '1',
        'id': 1,
        'locale': 'de',
    });
});
