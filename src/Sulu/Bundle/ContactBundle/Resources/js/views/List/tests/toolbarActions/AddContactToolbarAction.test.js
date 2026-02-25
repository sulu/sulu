// @flow
import React from 'react';
import {observable} from 'mobx';
import {Overlay} from 'sulu-admin-bundle/components';
import {ListStore, ResourceSingleSelect, SingleAutoComplete} from 'sulu-admin-bundle/containers';
import {ResourceRequester, Router} from 'sulu-admin-bundle/services';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {List} from 'sulu-admin-bundle/views';
import AddContactToolbarAction from '../../toolbarActions/AddContactToolbarAction';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/containers/List/stores/ListStore', () => jest.fn(function() {
    this.options = {};
    this.reload = jest.fn();
}));

jest.mock('sulu-admin-bundle/views/List/List', () => jest.fn());

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn());

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => ({
    put: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores/ResourceStore/ResourceStore', () => jest.fn(function() {
    this.data = {};
    this.setMultiple = jest.fn();
}));

function createAddContactToolbarAction() {
    const router = new Router({});
    const listStore = new ListStore('test', 'test', 'test', {page: observable.box(1)});
    const list = new List({
        route: router.route,
        router,
    });
    const locales = [];
    const resourceStore = new ResourceStore('test');

    return new AddContactToolbarAction(listStore, list, router, locales, resourceStore, {});
}

const findNode = (node, predicate) => {
    if (!node || typeof node !== 'object') {
        return undefined;
    }

    if (predicate(node)) {
        return node;
    }

    const children = React.Children.toArray(node.props && node.props.children).filter(Boolean);

    for (const child of children) {
        const result = findNode(child, predicate);

        if (result) {
            return result;
        }
    }

    return undefined;
};

const getOverlayNode = (addContactToolbarAction): any => {
    const overlayNode = addContactToolbarAction.getNode();

    if (overlayNode.type !== Overlay) {
        throw new Error('Overlay node was not found');
    }

    return overlayNode;
};

const getSingleAutoCompleteNode = (overlayNode): any => {
    const singleAutoCompleteNode = findNode(overlayNode, (node) => node.type === SingleAutoComplete);

    if (!singleAutoCompleteNode) {
        throw new Error('SingleAutoComplete node was not found');
    }

    return singleAutoCompleteNode;
};

const getResourceSingleSelectNode = (overlayNode): any => {
    const resourceSingleSelectNode = findNode(overlayNode, (node) => node.type === ResourceSingleSelect);

    if (!resourceSingleSelectNode) {
        throw new Error('ResourceSingleSelect node was not found');
    }

    return resourceSingleSelectNode;
};

test('Return config for toolbar item', () => {
    const addContactToolbarAction = createAddContactToolbarAction();

    expect(addContactToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-plus-circle',
        label: 'sulu_admin.add',
        type: 'button',
    }));
});

test('Open dialog if button is clicked', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    expect(getOverlayNode(addContactToolbarAction).props.open).toEqual(false);
    clickHandler();
    expect(getOverlayNode(addContactToolbarAction).props.open).toEqual(true);
});

test('Pass correct options to components', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;
    clickHandler();

    const overlayNode = getOverlayNode(addContactToolbarAction);
    const singleAutoCompleteNode = getSingleAutoCompleteNode(overlayNode);
    const resourceSingleSelectNode = getResourceSingleSelectNode(overlayNode);

    expect(resourceSingleSelectNode.props.editable).toEqual(true);
    expect(singleAutoCompleteNode.props.options).toEqual({excludedAccountId: 4, flat: false});
});

test('Reset fields if overlay is just closed', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;

    clickHandler();
    let overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props.open).toEqual(true);

    getSingleAutoCompleteNode(overlayNode).props.selectionStore.set({id: 3});
    getResourceSingleSelectNode(overlayNode).props.onChange(5);

    overlayNode = getOverlayNode(addContactToolbarAction);
    expect(getSingleAutoCompleteNode(overlayNode).props.selectionStore.item).toEqual({id: 3});
    expect(getResourceSingleSelectNode(overlayNode).props.value).toEqual(5);
    overlayNode.props.onClose();

    overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props.open).toEqual(false);

    clickHandler();
    overlayNode = getOverlayNode(addContactToolbarAction);
    expect(getSingleAutoCompleteNode(overlayNode).props.selectionStore.item).toEqual(undefined);
    expect(getResourceSingleSelectNode(overlayNode).props.value).toEqual(undefined);

    expect(ResourceRequester.put).not.toBeCalled();
});

test('Add selected contact to current account', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;
    const putPromise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(putPromise);

    clickHandler();
    let overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props.open).toEqual(true);
    expect(overlayNode.props.confirmDisabled).toEqual(true);
    getSingleAutoCompleteNode(overlayNode).props.selectionStore.set({id: 3});

    overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props.confirmDisabled).toEqual(false);

    overlayNode.props.onConfirm();

    overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.put).toBeCalledWith('account_contacts', {position: undefined}, {accountId: 4, id: 3});

    return putPromise.then(() => {
        overlayNode = getOverlayNode(addContactToolbarAction);
        expect(overlayNode.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        if (!addContactToolbarAction.resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addContactToolbarAction.listStore.reload).toBeCalledWith();
    });
});

test('Add selected contact to current account with position', () => {
    const addContactToolbarAction = createAddContactToolbarAction();
    addContactToolbarAction.listStore.options.accountId = 4;

    const clickHandler = addContactToolbarAction.getToolbarItemConfig().onClick;
    const putPromise = Promise.resolve();
    ResourceRequester.put.mockReturnValue(putPromise);

    clickHandler();
    let overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props.open).toEqual(true);
    getSingleAutoCompleteNode(overlayNode).props.selectionStore.set({id: 3});
    getResourceSingleSelectNode(overlayNode).props.onChange(5);

    overlayNode.props.onConfirm();
    overlayNode = getOverlayNode(addContactToolbarAction);
    expect(overlayNode.props).toEqual(expect.objectContaining({
        confirmLoading: true,
        open: true,
    }));

    expect(ResourceRequester.put).toBeCalledWith('account_contacts', {position: 5}, {accountId: 4, id: 3});

    return putPromise.then(() => {
        overlayNode = getOverlayNode(addContactToolbarAction);
        expect(overlayNode.props).toEqual(expect.objectContaining({
            confirmLoading: false,
            open: false,
        }));

        if (!addContactToolbarAction.resourceStore) {
            throw new Error('The resourceStore must be set on the ToolbarAction!');
        }

        expect(addContactToolbarAction.listStore.reload).toBeCalledWith();
    });
});
