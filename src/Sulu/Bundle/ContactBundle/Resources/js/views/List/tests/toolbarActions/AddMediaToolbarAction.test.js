// @flow
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import {ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {List} from 'sulu-admin-bundle/views';
import AddMediaToolbarAction from '../../toolbarActions/AddMediaToolbarAction';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.options = {};
    this.selectionIds = [];
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    patch: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore/ResourceStore', () => jest.fn(function() {
    this.data = {};
    this.setMultiple = jest.fn();
}));

function createAddMediaToolbarAction() {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new AddMediaToolbarAction(listStore, list, router, locales, resourceStore, {});
}

test('Return config for toolbar item', () => {
    const addMediaToolbarAction = createAddMediaToolbarAction();

    expect(addMediaToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-plus-circle',
        label: 'sulu_admin.add',
        type: 'button',
    }));
});

test('Open dialog if button is clicked', () => {
    const addMediaToolbarAction = createAddMediaToolbarAction();
    const clickHandler = addMediaToolbarAction.getToolbarItemConfig().onClick;

    expect(shallow(addMediaToolbarAction.getNode()).instance().props.open).toEqual(false);
    clickHandler();
    expect(shallow(addMediaToolbarAction.getNode()).instance().props.open).toEqual(true);
});

test('Do nothing if overlay is just closed', () => {
    const addMediaToolbarAction = createAddMediaToolbarAction();
    const clickHandler = addMediaToolbarAction.getToolbarItemConfig().onClick;

    clickHandler();
    expect(shallow(addMediaToolbarAction.getNode()).instance().props.open).toEqual(true);
    shallow(addMediaToolbarAction.getNode()).instance().props.onClose();
    expect(shallow(addMediaToolbarAction.getNode()).instance().props.open).toEqual(false);

    expect(ResourceRequester.patch).not.toBeCalled();
});

test('Delete selected items if confirm button is clicked', () => {
    const addMediaToolbarAction = createAddMediaToolbarAction();
    addMediaToolbarAction.listStore.options.contactId = 4;
    if (!addMediaToolbarAction.resourceStore) {
        throw new Error('The resourceStore must be set on the ToolbarAction!');
    }

    addMediaToolbarAction.resourceStore.data = {medias: [1, 2]};
    addMediaToolbarAction.resourceStore.resourceKey = 'contacts';

    const clickHandler = addMediaToolbarAction.getToolbarItemConfig().onClick;

    const patchResponse = {};
    const patchPromise = Promise.resolve(patchResponse);
    ResourceRequester.patch.mockReturnValue(patchPromise);

    clickHandler();
    let mediaOverlay = shallow(addMediaToolbarAction.getNode()).instance();
    expect(mediaOverlay.props.open).toEqual(true);
    mediaOverlay.props.onConfirm([{id: 3}, {id: 4}]);

    mediaOverlay = shallow(addMediaToolbarAction.getNode()).instance();
    expect(mediaOverlay.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.patch).toBeCalledWith('contacts', {medias: [1, 2, 3, 4]}, {id: 4});

    return patchPromise.then(() => {
        mediaOverlay = shallow(addMediaToolbarAction.getNode()).instance();
        expect(mediaOverlay.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = addMediaToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addMediaToolbarAction.listStore.reload).toBeCalledWith();
        expect(resourceStore.setMultiple).toBeCalledWith(patchResponse);
    });
});
