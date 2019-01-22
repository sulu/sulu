// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import DatagridStore from '../../../containers/Datagrid/stores/DatagridStore';
import DatagridOverlay from '../../../containers/DatagridOverlay';
import MultiDatagridOverlay from '../MultiDatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/DatagridOverlay', () => jest.fn(function DatagridOverlay(props) {
    return <div adapter={props.adapter} className="datagrid" />;
}));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(
    function(resourceKey, datagridKey, userSettingsKey, observableOptions, options) {
        this.resourceKey = resourceKey;
        this.datagridKey = datagridKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.selections = [];
    }
));

test('Should instantiate the DatagridStore with locale and options', () => {
    const locale = observable.box('en');
    const options = {};

    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            datagridKey="snippets_datagrid"
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={options}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.instance().datagridStore.datagridKey).toEqual('snippets_datagrid');
    expect(multiDatagridOverlay.instance().datagridStore.resourceKey).toEqual('snippets');
    expect(multiDatagridOverlay.instance().datagridStore.observableOptions.locale.get()).toEqual('en');
    expect(multiDatagridOverlay.instance().datagridStore.options).toBe(options);
});

test('Should instantiate the DatagridStore without locale and options', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.instance().datagridStore.observableOptions.locale).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('overlayType')).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            overlayType="dialog"
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('overlayType')).toEqual('dialog');
});

test('Should pass disabledIds to the DatagridOverlay', () => {
    const disabledIds = [1, 2, 5];

    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            datagridKey="snippets"
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(multiDatagridOverlay.find(DatagridOverlay).prop('allowActivateForDisabledItems')).toEqual(true);
});

test('Should pass reloadOnOpen to the Datagrid', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            reloadOnOpen={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('reloadOnOpen')).toEqual(true);
});

test('Should pass clearSelectionOnClose to the Datagrid', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            clearSelectionOnClose={true}
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('clearSelectionOnClose')).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the Datagrid', () => {
    const disabledIds = [1, 2, 5];

    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            datagridKey="snippets"
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(multiDatagridOverlay.find(DatagridOverlay).prop('allowActivateForDisabledItems')).toEqual(false);
});

test('Should pass confirmLoading flag to the Overlay', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            confirmLoading={true}
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{}]}
            resourceKey="snippets"
            title="Test"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('confirmLoading')).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const multiDatagridOverlay = mount(
        <MultiDatagridOverlay
            adapter="table"
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const datagridStore = multiDatagridOverlay.instance().datagridStore;
    datagridStore.selections = [{id: 1}, {id: 2}];

    expect(confirmSpy).not.toBeCalled();
    multiDatagridOverlay.find(DatagridOverlay).prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith([{id: 1}, {id: 2}]);
});

test('Should select the preSelectedItems in the DatagridStore', () => {
    shallow(
        <MultiDatagridOverlay
            adapter="table"
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(DatagridStore).toBeCalledWith(
        'snippets',
        'snippets',
        'multi_datagrid_overlay',
        expect.anything(),
        undefined,
        [1, 2, 3]
    );
});

test('Should not fail when preSelectedItems is undefined', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const datagridStore = multiDatagridOverlay.instance().datagridStore;

    expect(datagridStore.select).not.toBeCalled();
});

test('Should instantiate the datagrid with the passed adapter', () => {
    const multiDatagridOverlay1 = mount(
        <MultiDatagridOverlay
            adapter="table"
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(multiDatagridOverlay1.find(DatagridOverlay).prop('adapter')).toEqual('table');

    const multiDatagridOverlay2 = mount(
        <MultiDatagridOverlay
            adapter="column_list"
            datagridKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(multiDatagridOverlay2.find(DatagridOverlay).prop('adapter')).toEqual('column_list');
});
