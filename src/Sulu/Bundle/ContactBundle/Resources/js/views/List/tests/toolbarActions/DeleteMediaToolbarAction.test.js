// @flow
import {observable} from 'mobx';
import {Dialog} from 'sulu-admin-bundle/components';
import {ListStore} from 'sulu-admin-bundle/containers';
import {Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {List} from 'sulu-admin-bundle/views';
import DeleteMediaToolbarAction from '../../toolbarActions/DeleteMediaToolbarAction';

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.selectionIds = [];
    this.deleteSelection = jest.fn();
    this.deletingSelection = false;
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    patch: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore/ResourceStore', () => jest.fn(function() {
    this.data = {};
    this.setMultiple = jest.fn();
    this.set = jest.fn();
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

    return new DeleteMediaToolbarAction(listStore, list, router, locales, resourceStore, {});
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

    expect(deleteMediaToolbarAction.getNode().props.open).toEqual(false);
    clickHandler();
    expect(deleteMediaToolbarAction.getNode().props.open).toEqual(true);
});

test('Do nothing if cancel button is clicked', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();
    const clickHandler = deleteMediaToolbarAction.getToolbarItemConfig().onClick;

    clickHandler();
    expect(deleteMediaToolbarAction.getNode().props.open).toEqual(true);
    const deleteMediaDialogNode: any = deleteMediaToolbarAction.getNode();
    deleteMediaDialogNode.props.onCancel();
    expect(deleteMediaToolbarAction.getNode().props.open).toEqual(false);
});

test('Delete selected items if confirm button is clicked', () => {
    const deleteMediaToolbarAction = createDeleteMediaToolbarAction();
    // $FlowFixMe
    deleteMediaToolbarAction.listStore.selectionIds = [3, 4];
    if (!deleteMediaToolbarAction.resourceStore) {
        throw new Error('The resourceStore must be set on the ToolbarAction!');
    }

    deleteMediaToolbarAction.resourceStore.data = {medias: [1, 2, 3, 4, 5]};
    deleteMediaToolbarAction.resourceStore.resourceKey = 'contacts';

    const clickHandler = deleteMediaToolbarAction.getToolbarItemConfig().onClick;
    const deleteSelectionPromise = Promise.resolve();
    // $FlowFixMe
    deleteMediaToolbarAction.listStore.deleteSelection.mockReturnValue(deleteSelectionPromise);

    clickHandler();
    let deleteMediaDialogNode = deleteMediaToolbarAction.getNode();
    expect(deleteMediaDialogNode.type).toEqual(Dialog);
    expect(deleteMediaDialogNode.props.open).toEqual(true);
    deleteMediaDialogNode.props.onConfirm();

    deleteMediaToolbarAction.listStore.deletingSelection = true;
    expect(deleteMediaToolbarAction.listStore.deleteSelection).toBeCalledWith();

    deleteMediaDialogNode = deleteMediaToolbarAction.getNode();
    expect(deleteMediaDialogNode.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    return deleteSelectionPromise.then(() => {
        deleteMediaToolbarAction.listStore.deletingSelection = false;
        deleteMediaDialogNode = deleteMediaToolbarAction.getNode();
        expect(deleteMediaDialogNode.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        const resourceStore = deleteMediaToolbarAction.resourceStore;
        if (!resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(resourceStore.set).toBeCalledWith('medias', [1, 2, 5]);
    });
});
