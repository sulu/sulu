// @flow
import {observable} from 'mobx';
import {shallow} from 'enzyme';
import {ListStore} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {List} from 'sulu-admin-bundle/views';
import DeleteMediaToolbarAction from '../../toolbarActions/DeleteMediaToolbarAction';

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

function createDeleteMediaToolbarAction() {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new DeleteMediaToolbarAction(listStore, list, router, locales, resourceStore);
}

test('Return config for toolbar item', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();
    // $FlowFixMe
    deleteMediaToolbarAction.listStore.selectionIds = [1];

    expect(deleteMediaToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        type: 'button',
    }));
});

test('Return config for toolbar item when nothing is selected', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();

    expect(deleteMediaToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        type: 'button',
    }));
});

test('Open dialog if button is clicked', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();
    const clickHandler = deleteMediaToolbarAction.getToolbarItemConfig().onClick;

    expect(shallow(deleteMediaToolbarAction.getNode()).instance().props.open).toEqual(false);
    clickHandler();
    expect(shallow(deleteMediaToolbarAction.getNode()).instance().props.open).toEqual(true);
});

test('Do nothing if cancel button is clicked', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();
    const clickHandler = deleteMediaToolbarAction.getToolbarItemConfig().onClick;

    clickHandler();
    expect(shallow(deleteMediaToolbarAction.getNode()).instance().props.open).toEqual(true);
    shallow(deleteMediaToolbarAction.getNode()).instance().props.onCancel();
    expect(shallow(deleteMediaToolbarAction.getNode()).instance().props.open).toEqual(false);

    expect(ResourceRequester.patch).not.toBeCalled();
});

test('Delete selected items if confirm button is clicked', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();
    deleteMediaToolbarAction.listStore.options.id = 4;
    // $FlowFixMe
    deleteMediaToolbarAction.listStore.selectionIds = [3, 4];
    if (!deleteMediaToolbarAction.resourceStore) {
        throw new Error('The resourceStore must be set on the ToolbarAction!');
    }

    deleteMediaToolbarAction.resourceStore.data = {medias: [1, 2, 3, 4, 5]};
    deleteMediaToolbarAction.resourceStore.resourceKey = 'contacts';

    const clickHandler = deleteMediaToolbarAction.getToolbarItemConfig().onClick;

    const patchResponse = {};
    const patchPromise = Promise.resolve(patchResponse);
    ResourceRequester.patch.mockReturnValue(patchPromise);

    clickHandler();
    let deleteMediaDialog = shallow(deleteMediaToolbarAction.getNode()).instance();
    expect(deleteMediaDialog.props.open).toEqual(true);
    deleteMediaDialog.props.onConfirm();

    deleteMediaDialog = shallow(deleteMediaToolbarAction.getNode()).instance();
    expect(deleteMediaDialog.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.patch).toBeCalledWith('contacts', {medias: [1, 2, 5]}, {id: 4});

    return patchPromise.then(() => {
        deleteMediaDialog = shallow(deleteMediaToolbarAction.getNode()).instance();
        expect(deleteMediaDialog.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = deleteMediaToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(deleteMediaToolbarAction.listStore.reload).toBeCalledWith();
        expect(resourceStore.setMultiple).toBeCalledWith(patchResponse);
    });
});
