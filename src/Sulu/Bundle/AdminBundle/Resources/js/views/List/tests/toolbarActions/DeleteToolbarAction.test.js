// @flow
import {observable} from 'mobx';
import ListStore from '../../../../containers/List/stores/ListStore';
import Router from '../../../../services/Router';
import ResourceStore from '../../../../stores/ResourceStore';
import List from '../../../../views/List';
import DeleteToolbarAction from '../../toolbarActions/DeleteToolbarAction';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../containers/List/stores/ListStore', () => jest.fn(function() {
    this.selectionIds = [];
    this.selections = [];
    this.deletingSelection = false;
}));

jest.mock('../../../../views/List/List', () => jest.fn(function() {
    this.requestSelectionDelete = jest.fn();
}));

jest.mock('../../../../services/Router/Router', () => jest.fn());

function createDeleteToolbarAction(options = {}) {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new DeleteToolbarAction(listStore, list, router, locales, resourceStore, options);
}

test('Return config for toolbar item', () => {
    const deleteToolbarAction = createDeleteToolbarAction();

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        loading: false,
        type: 'button',
    }));
});

test('Return disabled config for toolbar item if one selected item fulfills the passed disabled_condition', () => {
    const deleteToolbarAction = createDeleteToolbarAction({disabled_condition: 'url == "/"'});

    deleteToolbarAction.listStore.selectionIds.push(1);
    deleteToolbarAction.listStore.selections.push({id: 1, url: '/test1'});

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        loading: false,
        type: 'button',
    }));

    deleteToolbarAction.listStore.selectionIds.push(2);
    deleteToolbarAction.listStore.selections.push({id: 2, url: '/'});

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: true,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        loading: false,
        type: 'button',
    }));
});

test('Return config for toolbar item with selection and currently deleting', () => {
    const deleteToolbarAction = createDeleteToolbarAction();

    deleteToolbarAction.listStore.selectionIds.push(1);
    deleteToolbarAction.listStore.deletingSelection = true;

    expect(deleteToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        disabled: false,
        icon: 'su-trash-alt',
        label: 'sulu_admin.delete',
        loading: true,
        type: 'button',
    }));
});

test.each([
    true,
    false,
])('Call requestSelectionDelete of list with an allowConflictDeletion value of %s', (allowConflictDeletion) => {
    const deleteToolbarAction = createDeleteToolbarAction({allow_conflict_deletion: allowConflictDeletion});

    deleteToolbarAction.listStore.selectionIds.push(1);

    deleteToolbarAction.getToolbarItemConfig().onClick();

    expect(deleteToolbarAction.list.requestSelectionDelete).toBeCalledWith(allowConflictDeletion);
});
