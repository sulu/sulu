// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import DialogComponent from '../../../components/Dialog';
import OverlayComponent from '../../../components/Overlay';
import ListComponent from '../../../containers/List';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlay from '../ListOverlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../components/Dialog', () => {
    const Dialog: any = jest.fn(function Dialog(props) {
        return <div>{props.children}</div>;
    });
    Dialog.defaultProps = {
        confirmLoading: false,
    };

    return Dialog;
});

jest.mock('../../../components/Overlay', () => {
    const Overlay: any = jest.fn(function Overlay(props) {
        return <div>{props.children}</div>;
    });
    Overlay.defaultProps = {
        confirmLoading: false,
    };

    return Overlay;
});

jest.mock('../../../containers/List', () => jest.fn(function List() {
    return <div />;
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, observableOptions, options) {
        this.options = options;
        this.clearSelection = jest.fn();
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.setActive = jest.fn();
        this.selections = [];
        this.reset = jest.fn();
        this.reload = jest.fn();
        this.loading = false;
    }
));

function createListStore() {
    return new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
}

function getLatestOverlayProps() {
    const calls = (OverlayComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function getLatestListProps() {
    const calls = (ListComponent: any).mock.calls;

    return calls[calls.length - 1][0];
}

function renderListOverlay(props = {}) {
    let currentProps: any = {
        adapter: 'table',
        listStore: createListStore(),
        onClose: jest.fn(),
        onConfirm: jest.fn(),
        open: false,
        title: 'Selection',
        ...props,
    };

    const {rerender} = render(<ListOverlay {...(currentProps: any)} />);

    return {
        listStore: currentProps.listStore,
        rerenderListOverlay: (nextProps: any = {}) => {
            currentProps = {...currentProps, ...nextProps};
            rerender(<ListOverlay {...(currentProps: any)} />);
        },
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should use an Overlay by default', () => {
    renderListOverlay({
        disabledIds: [1, 2, 5],
    });

    expect(DialogComponent).toHaveBeenCalledTimes(0);
    expect(OverlayComponent).toHaveBeenCalledTimes(1);
});

test('Should use a dialog if overlayType is set to dialog', () => {
    renderListOverlay({
        disabledIds: [1, 2, 5],
        overlayType: 'dialog',
    });

    expect(DialogComponent).toHaveBeenCalledTimes(1);
    expect(OverlayComponent).toHaveBeenCalledTimes(0);
});

test('Should pass disabledIds to the List', () => {
    const disabledIds = [1, 2, 5];

    renderListOverlay({
        disabledIds,
    });

    expect(getLatestListProps().disabledIds).toBe(disabledIds);
    expect(getLatestListProps().allowActivateForDisabledItems).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the List', () => {
    const disabledIds = [1, 2, 5];

    renderListOverlay({
        allowActivateForDisabledItems: false,
        disabledIds,
    });

    expect(getLatestListProps().disabledIds).toBe(disabledIds);
    expect(getLatestListProps().allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the List', () => {
    renderListOverlay({
        itemDisabledCondition: 'status == "inactive"',
    });

    expect(getLatestListProps().itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass correct flags to the List', () => {
    renderListOverlay({
        disabledIds: [1, 2, 5],
    });

    expect(getLatestListProps().copyable).toEqual(false);
    expect(getLatestListProps().deletable).toEqual(false);
    expect(getLatestListProps().movable).toEqual(false);
    expect(getLatestListProps().orderable).toEqual(false);
    expect(getLatestListProps().searchable).toEqual(true);
});

test('Should pass confirmLoading and confirmDisabled flag to the Overlay', () => {
    renderListOverlay({
        open: true,
        title: 'Test',
    });

    expect(getLatestOverlayProps().confirmLoading).toEqual(false);
    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);
});

test('Should pass confirmLoading and negative confirmDisabled flag to the Overlay', () => {
    renderListOverlay({
        confirmLoading: true,
        open: true,
        preSelectedItems: [{}],
        title: 'Test',
    });

    expect(getLatestOverlayProps().confirmLoading).toEqual(true);
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);
});

test('Should call onConfirm when the confirm button is clicked', () => {
    const confirmSpy = jest.fn();
    const {listStore} = renderListOverlay({
        onConfirm: confirmSpy,
        open: true,
        preSelectedItems: [{id: 1}, {id: 2}, {id: 3}],
    });

    listStore.selections = [{id: 1}, {id: 2}];

    expect(confirmSpy).toBeCalledTimes(0);
    getLatestOverlayProps().onConfirm();
    expect(confirmSpy).toBeCalledWith();
});

test('Should instantiate the list with the passed adapter', () => {
    renderListOverlay({
        adapter: 'table',
        open: true,
        title: 'test',
    });
    expect((ListComponent: any).mock.calls[0][0].adapters).toEqual(['table']);

    renderListOverlay({
        adapter: 'column_list',
        open: true,
        title: 'test',
    });
    expect((ListComponent: any).mock.calls[1][0].adapters).toEqual(['column_list']);
});

test('Should reload on open if reloadOnOpen is set to true', () => {
    const {listStore, rerenderListOverlay} = renderListOverlay({
        open: false,
        preSelectedItems: [{id: 1}],
        reloadOnOpen: true,
        title: 'test',
    });

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();

    rerenderListOverlay({
        open: true,
    });

    expect(listStore.reset).toBeCalledWith();
    expect(listStore.reload).toBeCalledWith();
});

test('Should not reload on open if reloadOnOpen is set to true but listStore is still loading', () => {
    const listStore = createListStore();
    // $FlowFixMe
    listStore.loading = true;

    const {rerenderListOverlay} = renderListOverlay({
        listStore,
        open: false,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();

    rerenderListOverlay({
        open: true,
    });

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();
});

test('Should not reload on open if reloadOnOpen is not set', () => {
    const {listStore, rerenderListOverlay} = renderListOverlay({
        open: false,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();

    rerenderListOverlay({
        open: true,
    });

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();
});

test('Should not clear selection on close if clearSelectionOnClose prop is not set', () => {
    const {listStore, rerenderListOverlay} = renderListOverlay({
        clearSelectionOnClose: false,
        open: true,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    expect(listStore.clearSelection).not.toBeCalled();
    expect(listStore.select).not.toBeCalled();

    rerenderListOverlay({
        open: false,
    });

    expect(listStore.clearSelection).not.toBeCalled();
});

test('Should clear selection on close if clearSelectionOnClose prop is set', () => {
    const {listStore, rerenderListOverlay} = renderListOverlay({
        clearSelectionOnClose: true,
        open: true,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    expect(listStore.clearSelection).not.toBeCalled();
    expect(listStore.select).not.toBeCalled();

    rerenderListOverlay({
        open: false,
    });

    expect(listStore.clearSelection).toBeCalledWith();
});

test('Should update selection if passed preSelectedItems prop changes', () => {
    const {listStore, rerenderListOverlay} = renderListOverlay({
        open: true,
        preSelectedItems: [{id: 1}],
        title: 'test',
    });

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    rerenderListOverlay({
        title: 'bla',
    });

    expect(listStore.clearSelection).not.toBeCalled();
    expect(listStore.select).not.toBeCalled();

    rerenderListOverlay({
        preSelectedItems: [{id: 2}],
    });

    expect(listStore.clearSelection).toBeCalledWith();
    expect(listStore.select).toBeCalledWith({id: 2});
});
