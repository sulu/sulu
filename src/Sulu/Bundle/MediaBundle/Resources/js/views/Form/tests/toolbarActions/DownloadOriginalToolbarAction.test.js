// @flow
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Router from 'sulu-admin-bundle/services/Router';
import Form from 'sulu-admin-bundle/views/Form/Form';
import {ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import DownloadOriginalToolbarAction from '../../toolbarActions/DownloadOriginalToolbarAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function() {
    this.data = {};
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get data() {
            return this.resourceStore.data;
        }
    }
));

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/views/Form/Form', () => jest.fn());

function createAction(data: Object) {
    const resourceStore = new ResourceStore('media');
    Object.assign(resourceStore.data, data);
    const resourceFormStore = new ResourceFormStore(resourceStore, 'media');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new DownloadOriginalToolbarAction(resourceFormStore, form, router, [], {}, resourceStore);
}

test('Return a download button for the original file', () => {
    const toolbarItemConfig = createAction({adminUrl: '/admin/media/1/image.jpg'}).getToolbarItemConfig();

    expect(toolbarItemConfig).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-download',
        label: 'sulu_media.download_masterfile',
        type: 'button',
    }));
});

test('Disable the download button if the media has no url', () => {
    expect(createAction({}).getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: true}));
});

test('Download the original file with the adminUrl', () => {
    const hrefs = [];
    const click = jest.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function() {
        hrefs.push(this.href);
    });

    const toolbarItemConfig: Object = createAction({
        adminUrl: '/admin/media/1/image.jpg',
        url: '/media/1/image.jpg',
    }).getToolbarItemConfig();
    toolbarItemConfig.onClick();

    expect(hrefs).toEqual(['http://localhost/admin/media/1/image.jpg?inline=0']);

    click.mockRestore();
});
