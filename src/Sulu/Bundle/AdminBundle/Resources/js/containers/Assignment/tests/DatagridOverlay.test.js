// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import Datagrid from '../../../containers/Datagrid';
import DatagridOverlay from '../DatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => jest.fn(function Datagrid(props) {
    return <div className="datagrid" adapter={props.adapter} />;
}));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function(resourceKey, observableOptions) {
    this.clearSelection = jest.fn();
    this.observableOptions = observableOptions;
    this.select = jest.fn();
    this.setActive = jest.fn();
}));

test('Should instantiate the DatagridStore with locale', () => {
    const locale = observable.box('en');
    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    expect(datagridOverlay.instance().datagridStore.observableOptions.locale.get()).toEqual('en');
});

test('Should instantiate the DatagridStore without locale', () => {
    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    expect(datagridOverlay.instance().datagridStore.observableOptions.locale).toEqual(undefined);
});

test('Should pass disabledIds to the Datagrid', () => {
    const disabledIds = [1, 2, 5];

    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    expect(datagridOverlay.find(Datagrid).prop('disabledIds')).toBe(disabledIds);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const datagridOverlay = mount(
        <DatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    const datagridStore = datagridOverlay.instance().datagridStore;
    datagridStore.selections = [{id: 1}, {id: 2}];

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    expect(confirmSpy).toBeCalledWith([{id: 1}, {id: 2}]);
});

test('Should select the preSelectedItems in the DatagridStore', () => {
    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    const datagridStore = datagridOverlay.instance().datagridStore;

    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 2});
    expect(datagridStore.select).toBeCalledWith({id: 3});
});

test('Should not fail when preSelectedItems is undefined', () => {
    const datagridOverlay = shallow(
        <DatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    const datagridStore = datagridOverlay.instance().datagridStore;

    expect(datagridStore.select).not.toBeCalled();
});

test('Should instantiate the datagrid with the passed adapter', () => {
    const datagridOverlay1 = mount(
        <DatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(datagridOverlay1.find(Datagrid).prop('adapters')).toEqual(['table']);

    const datagridOverlay2 = mount(
        <DatagridOverlay
            adapter="column_list"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(datagridOverlay2.find(Datagrid).prop('adapters')).toEqual(['column_list']);
});

test('Should reset active item when reopening because a complete tree to a certain item cannot be opened yet', () => {
    const datagridOverlay = mount(
        <DatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="test"
        />
    );

    datagridOverlay.setProps({open: true});

    expect(datagridOverlay.instance().datagridStore.setActive).toBeCalledWith(undefined);
});
