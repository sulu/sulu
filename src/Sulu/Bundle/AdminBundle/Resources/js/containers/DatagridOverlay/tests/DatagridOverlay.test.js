// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import Overlay from '../../../components/Overlay';
import Datagrid from '../../../containers/Datagrid';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';
import DatagridOverlay from '../DatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => jest.fn(function Datagrid(props) {
    return <div className="datagrid" adapter={props.adapter} />;
}));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(
    function(resourceKey, observableOptions, options) {
        this.options = options;
        this.clearSelection = jest.fn();
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.setActive = jest.fn();
        this.selections = [];
    }
));

test('Should pass disabledIds to the Datagrid', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            datagridStore={datagridStore}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(datagridOverlay.find(Datagrid).prop('disabledIds')).toBe(disabledIds);
    expect(datagridOverlay.find(Datagrid).prop('allowActivateForDisabledItems')).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the Datagrid', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            datagridStore={datagridStore}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(datagridOverlay.find(Datagrid).prop('disabledIds')).toBe(disabledIds);
    expect(datagridOverlay.find(Datagrid).prop('allowActivateForDisabledItems')).toEqual(false);
});

test('Should pass copyable, deletable, movable, confirmDisabled and confirmLoading flag to the Datagrid', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});
    const disabledIds = [1, 2, 5];

    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            datagridStore={datagridStore}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            title="Selection"
        />
    );

    expect(datagridOverlay.find(Datagrid).prop('copyable')).toEqual(false);
    expect(datagridOverlay.find(Datagrid).prop('deletable')).toEqual(false);
    expect(datagridOverlay.find(Datagrid).prop('movable')).toEqual(false);
});

test('Should pass confirmLoading and confirmDisabled flag to the Overlay', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});

    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="Test"
        />
    );

    expect(datagridOverlay.find(Overlay).prop('confirmLoading')).toEqual(false);
    expect(datagridOverlay.find(Overlay).prop('confirmDisabled')).toEqual(true);
});

test('Should pass confirmLoading and negative confirmDisabled flag to the Overlay', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});

    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            confirmLoading={true}
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{}]}
            title="Test"
        />
    );

    expect(datagridOverlay.find(Overlay).prop('confirmLoading')).toEqual(true);
    expect(datagridOverlay.find(Overlay).prop('confirmDisabled')).toEqual(false);
});

test('Should call onConfirm when the confirm button is clicked', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});
    const confirmSpy = jest.fn();
    mount(
        <DatagridOverlay
            adapter="table"
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            title="Selection"
        />
    );

    datagridStore.selections = [{id: 1}, {id: 2}];

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    expect(confirmSpy).toBeCalledWith();
});

test('Should instantiate the datagrid with the passed adapter', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});

    const datagridOverlay1 = mount(
        <DatagridOverlay
            adapter="table"
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="test"
        />
    );
    expect(datagridOverlay1.find(Datagrid).prop('adapters')).toEqual(['table']);

    const datagridOverlay2 = mount(
        <DatagridOverlay
            adapter="column_list"
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            title="test"
        />
    );
    expect(datagridOverlay2.find(Datagrid).prop('adapters')).toEqual(['column_list']);
});

test('Should not clear selection on close if clearSelectionOnClose prop is not set', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});

    const datagridOverlay = mount(
        <DatagridOverlay
            adapter="table"
            clearSelectionOnClose={false}
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    datagridStore.clearSelection.mockReset();
    datagridStore.select.mockReset();

    expect(datagridStore.clearSelection).not.toBeCalled();
    expect(datagridStore.select).not.toBeCalled();

    datagridOverlay.setProps({
        open: false,
    });

    expect(datagridStore.clearSelection).not.toBeCalled();
});

test('Should clear selection on close if clearSelectionOnClose prop is set', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});

    const datagridOverlay = mount(
        <DatagridOverlay
            adapter="table"
            clearSelectionOnClose={true}
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    datagridStore.clearSelection.mockReset();
    datagridStore.select.mockReset();

    expect(datagridStore.clearSelection).not.toBeCalled();
    expect(datagridStore.select).not.toBeCalled();

    datagridOverlay.setProps({
        open: false,
    });

    expect(datagridStore.clearSelection).toBeCalledWith();
});

test('Should update selection if passed preSelectedItems prop changes', () => {
    const datagridStore = new DatagridStore('snippets', {page: observable.box(1)});

    const datagridOverlay = mount(
        <DatagridOverlay
            adapter="table"
            datagridStore={datagridStore}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}]}
            title="test"
        />
    );

    datagridStore.clearSelection.mockReset();
    datagridStore.select.mockReset();

    datagridOverlay.setProps({
        title: 'bla',
    });

    expect(datagridStore.clearSelection).not.toBeCalled();
    expect(datagridStore.select).not.toBeCalled();

    datagridOverlay.setProps({
        preSelectedItems: [{id: 2}],
    });

    expect(datagridStore.clearSelection).toBeCalledWith();
    expect(datagridStore.select).toBeCalledWith({id: 2});
});
