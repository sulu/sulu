// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import DatagridOverlay from '../DatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => function Datagrid() {
    return <div className="datagrid" />;
});

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function(resourceKey, observableOptions) {
    this.observableOptions = observableOptions;
    this.select = jest.fn();
}));

test('Should instantiate the DatagridStore with locale', () => {
    const locale = observable('en');
    const datagridOverlay = shallow(
        <DatagridOverlay
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
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    expect(datagridOverlay.instance().datagridStore.observableOptions.locale).toEqual(undefined);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const datagridOverlay = mount(
        <DatagridOverlay
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
