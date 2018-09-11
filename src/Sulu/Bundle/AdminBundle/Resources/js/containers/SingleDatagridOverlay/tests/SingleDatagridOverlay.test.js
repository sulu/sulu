// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';
import DatagridOverlay from '../../../containers/DatagridOverlay';
import SingleDatagridOverlay from '../SingleDatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/DatagridOverlay', () => jest.fn(function DatagridOverlay(props) {
    return <div adapter={props.adapter} className="datagrid" />;
}));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(
    function(resourceKey, observableOptions, options) {
        this.options = options;
        this.observableOptions = observableOptions;
        this.select = jest.fn();

        mockExtendObservable(this, {
            selections: [],
        });

        this.clearSelection = jest.fn();
        this.destroy = jest.fn();
    }
));

test('Should instantiate the DatagridStore with locale and options', () => {
    const locale = observable.box('en');
    const options = {};

    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={options}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.instance().datagridStore.observableOptions.locale.get()).toEqual('en');
    expect(singleDatagridOverlay.instance().datagridStore.options).toBe(options);
});

test('Should instantiate the DatagridStore without locale and options', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.instance().datagridStore.observableOptions.locale).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.find(DatagridOverlay).prop('overlayType')).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            overlayType="dialog"
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.find(DatagridOverlay).prop('overlayType')).toEqual('dialog');
});

test('Should pass disabledIds to the DatagridOverlay', () => {
    const disabledIds = [1, 2, 5];

    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.find(DatagridOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(singleDatagridOverlay.find(DatagridOverlay).prop('allowActivateForDisabledItems')).toEqual(true);
});

test('Should pass clearSelectionOnClose to the Datagrid', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            clearSelectionOnClose={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.find(DatagridOverlay).prop('clearSelectionOnClose')).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the Datagrid', () => {
    const disabledIds = [1, 2, 5];

    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleDatagridOverlay.find(DatagridOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(singleDatagridOverlay.find(DatagridOverlay).prop('allowActivateForDisabledItems')).toEqual(false);
});

test('Should pass confirmLoading flag to the Overlay', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            confirmLoading={true}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Test"
        />
    );

    expect(singleDatagridOverlay.find(DatagridOverlay).prop('confirmLoading')).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const singleDatagridOverlay = mount(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const datagridStore = singleDatagridOverlay.instance().datagridStore;
    datagridStore.selections = [{id: 1}];

    expect(confirmSpy).not.toBeCalled();
    singleDatagridOverlay.find(DatagridOverlay).prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith({id: 1});
});

test('Should pass the id froms the preSelectedItems to the DatagridStore', () => {
    shallow(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItem={{id: 1}}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(DatagridStore).toBeCalledWith('snippets', expect.anything(), undefined, [1]);
});

test('Should not fail when preSelectedItem is undefined', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const datagridStore = singleDatagridOverlay.instance().datagridStore;

    expect(datagridStore.select).not.toBeCalled();
});

test('Should instantiate the datagrid with the passed adapter', () => {
    const singleDatagridOverlay1 = mount(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(singleDatagridOverlay1.find(DatagridOverlay).prop('adapter')).toEqual('table');

    const singleDatagridOverlay2 = mount(
        <SingleDatagridOverlay
            adapter="column_list"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(singleDatagridOverlay2.find(DatagridOverlay).prop('adapter')).toEqual('column_list');
});

test('Should only select a single item at a time', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItem={{id: 5}}
            resourceKey="snippets"
            title="test"
        />
    );

    singleDatagridOverlay.instance().datagridStore.selections.push({id: 3});
    expect(singleDatagridOverlay.instance().datagridStore.selections).toEqual([{id: 3}]);

    singleDatagridOverlay.instance().datagridStore.selections.push({id: 5});
    expect(singleDatagridOverlay.instance().datagridStore.clearSelection).toBeCalledWith();
    expect(singleDatagridOverlay.instance().datagridStore.select).toBeCalledWith({id: 5});
});

test('Should destroy datagridStore and autorun when unmounted', () => {
    const singleDatagridOverlay = shallow(
        <SingleDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItem={{id: 5}}
            resourceKey="snippets"
            title="test"
        />
    );

    const datagridStore = singleDatagridOverlay.instance().datagridStore;
    const selectionDisposerSpy = jest.fn();
    singleDatagridOverlay.instance().selectionDisposer = selectionDisposerSpy;
    singleDatagridOverlay.unmount();

    expect(datagridStore.destroy).toBeCalledWith();
    expect(selectionDisposerSpy).toBeCalledWith();
});
