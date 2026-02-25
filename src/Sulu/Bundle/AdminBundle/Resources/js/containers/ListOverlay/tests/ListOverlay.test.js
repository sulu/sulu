// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render} from '@testing-library/react';
import Dialog from '../../../components/Dialog';
import Overlay from '../../../components/Overlay';
import List from '../../../containers/List';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlay from '../ListOverlay';
import getLatestMockProps from '../../../utils/TestHelper/getLatestMockProps';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../components/Overlay', () => jest.fn(function OverlayMock({children}) {
    return <div>{children}</div>;
}));

jest.mock('../../../components/Dialog', () => jest.fn(function DialogMock({children}) {
    return <div>{children}</div>;
}));

jest.mock('../../../containers/List', () => jest.fn(function ListMock(props) {
    return <div className={props.adapters ? 'list' : ''} />;
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

function getLatestOverlayProps() {
    return getLatestMockProps((Overlay: any));
}

function getLatestListProps() {
    return getLatestMockProps((List: any));
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Should use an Overlay by default', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    render(
        <ListOverlay
            adapter="table"
            disabledIds={disabledIds}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect((Dialog: any)).not.toBeCalled();
    expect((Overlay: any)).toBeCalledTimes(1);
});

test('Should use a dialog if overlayType is set to dialog', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    render(
        <ListOverlay
            adapter="table"
            disabledIds={disabledIds}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            overlayType="dialog"
            title="Selection"
        />
    );

    expect((Dialog: any)).toBeCalledTimes(1);
    expect((Overlay: any)).not.toBeCalled();
});

test('Should pass disabledIds to the List', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    render(
        <ListOverlay
            adapter="table"
            disabledIds={disabledIds}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(getLatestListProps().disabledIds).toBe(disabledIds);
    expect(getLatestListProps().allowActivateForDisabledItems).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the List', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    render(
        <ListOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            disabledIds={disabledIds}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(getLatestListProps().disabledIds).toBe(disabledIds);
    expect(getLatestListProps().allowActivateForDisabledItems).toEqual(false);
});

test('Should pass itemDisabledCondition to the List', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    render(
        <ListOverlay
            adapter="table"
            itemDisabledCondition='status == "inactive"'
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(getLatestListProps().itemDisabledCondition).toBe('status == "inactive"');
});

test('Should pass correct flags to the List', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    render(
        <ListOverlay
            adapter="table"
            disabledIds={disabledIds}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(getLatestListProps().copyable).toEqual(false);
    expect(getLatestListProps().deletable).toEqual(false);
    expect(getLatestListProps().movable).toEqual(false);
    expect(getLatestListProps().orderable).toEqual(false);
    expect(getLatestListProps().searchable).toEqual(true);
});

test('Should pass confirmLoading and confirmDisabled flag to the Overlay', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="Test"
        />
    );

    expect(getLatestOverlayProps().confirmLoading).toEqual(undefined);
    expect(getLatestOverlayProps().confirmDisabled).toEqual(true);
});

test('Should pass confirmLoading and negative confirmDisabled flag to the Overlay', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    render(
        <ListOverlay
            adapter="table"
            confirmLoading={true}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{}]}
            title="Test"
        />
    );

    expect(getLatestOverlayProps().confirmLoading).toEqual(true);
    expect(getLatestOverlayProps().confirmDisabled).toEqual(false);
});

test('Should call onConfirm when the confirm button is clicked', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const confirmSpy = jest.fn();

    render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            title="Selection"
        />
    );

    listStore.selections = [{id: 1}, {id: 2}];

    act(() => {
        getLatestOverlayProps().onConfirm();
    });

    expect(confirmSpy).toBeCalledWith();
});

test('Should instantiate the list with the passed adapter', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="test"
        />
    );

    expect(getLatestListProps().adapters).toEqual(['table']);

    rerender(
        <ListOverlay
            adapter="column_list"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="test"
        />
    );

    expect(getLatestListProps().adapters).toEqual(['column_list']);
});

test('Should reload on open if reloadOnOpen is set to true', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={[{id: 1}]}
            reloadOnOpen={true}
            title="test"
        />
    );

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            reloadOnOpen={true}
            title="test"
        />
    );

    expect(listStore.reset).toBeCalledWith();
    expect(listStore.reload).toBeCalledWith();
});

test('Should not reload on open if reloadOnOpen is set to true but listStore is still loading', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    (listStore: any).loading = true;

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();
});

test('Should not reload on open if reloadOnOpen is not set', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    listStore.reset.mockReset();
    listStore.reload.mockReset();

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    expect(listStore.reset).not.toBeCalled();
    expect(listStore.reload).not.toBeCalled();
});

test('Should not clear selection on close if clearSelectionOnClose prop is not set', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const preSelectedItems = [{id: 1}];

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            clearSelectionOnClose={false}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={preSelectedItems}
            title="test"
        />
    );

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    expect(listStore.clearSelection).not.toBeCalled();
    expect(listStore.select).not.toBeCalled();

    rerender(
        <ListOverlay
            adapter="table"
            clearSelectionOnClose={false}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={preSelectedItems}
            title="test"
        />
    );

    expect(listStore.clearSelection).not.toBeCalled();
});

test('Should clear selection on close if clearSelectionOnClose prop is set', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            clearSelectionOnClose={true}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    expect(listStore.clearSelection).not.toBeCalled();
    expect(listStore.select).not.toBeCalled();

    rerender(
        <ListOverlay
            adapter="table"
            clearSelectionOnClose={true}
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    expect(listStore.clearSelection).toBeCalledWith();
});

test('Should update selection if passed preSelectedItems prop changes', () => {
    const listStore = new ListStore('snippets', 'snippets', 'list_overlay_test', {page: observable.box(1)});
    const preSelectedItems = [{id: 1}];

    const {rerender} = render(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={preSelectedItems}
            title="test"
        />
    );

    listStore.clearSelection.mockReset();
    listStore.select.mockReset();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={preSelectedItems}
            title="bla"
        />
    );

    expect(listStore.clearSelection).not.toBeCalled();
    expect(listStore.select).not.toBeCalled();

    rerender(
        <ListOverlay
            adapter="table"
            listStore={listStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 2}]}
            title="bla"
        />
    );

    expect(listStore.clearSelection).toBeCalledWith();
    expect(listStore.select).toBeCalledWith({id: 2});
});
