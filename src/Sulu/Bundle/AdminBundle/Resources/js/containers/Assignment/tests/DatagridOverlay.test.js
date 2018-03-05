// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import DatagridOverlay from '../DatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Datagrid', () => function Datagrid() {
    return <div className="datagrid" />;
});

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function() {
    this.select = jest.fn();
}));

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const datagridOverlay = mount(
        <DatagridOverlay
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            preSelectedIds={[1, 2, 3]}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    const datagridStore = datagridOverlay.instance().datagridStore;
    datagridStore.selections = [1, 2];

    const confirmButton = document.querySelector('button.primary');
    if (confirmButton) {
        confirmButton.click();
    }

    expect(confirmSpy).toBeCalledWith([1, 2]);
});

test('Should select the preSelectedIds in the DatagridStore', () => {
    const datagridOverlay = shallow(
        <DatagridOverlay
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedIds={[1, 2, 3]}
            resourceKey="snippets"
            title="Assignment"
        />
    );

    const datagridStore = datagridOverlay.instance().datagridStore;

    expect(datagridStore.select).toBeCalledWith(1);
    expect(datagridStore.select).toBeCalledWith(2);
    expect(datagridStore.select).toBeCalledWith(3);
});

test('Should not fail when preSelectedIds is undefined', () => {
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
