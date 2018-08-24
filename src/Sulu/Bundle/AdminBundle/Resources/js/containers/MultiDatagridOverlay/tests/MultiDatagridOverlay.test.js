// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import DatagridOverlay from '../../../containers/DatagridOverlay';
import MultiDatagridOverlay from '../MultiDatagridOverlay';

jest.mock('../../../utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/DatagridOverlay', () => jest.fn(function DatagridOverlay(props) {
    return <div className="datagrid" adapter={props.adapter} />;
}));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(
    function(resourceKey, observableOptions, options) {
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
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={options}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.instance().datagridStore.observableOptions.locale.get()).toEqual('en');
    expect(multiDatagridOverlay.instance().datagridStore.options).toBe(options);
});

test('Should instantiate the DatagridStore without locale and options', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.instance().datagridStore.observableOptions.locale).toEqual(undefined);
});

test('Should pass disabledIds to the DatagridOverlay', () => {
    const disabledIds = [1, 2, 5];

    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowDisabledActivation={true}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(multiDatagridOverlay.find(DatagridOverlay).prop('allowDisabledActivation')).toEqual(true);
});

test('Should pass allowDisabledActivation to the Datagrid', () => {
    const disabledIds = [1, 2, 5];

    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            allowDisabledActivation={false}
            disabledIds={disabledIds}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiDatagridOverlay.find(DatagridOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(multiDatagridOverlay.find(DatagridOverlay).prop('allowDisabledActivation')).toEqual(false);
});

test('Should pass confirmLoading flag to the Overlay', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            confirmLoading={true}
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
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const datagridStore = multiDatagridOverlay.instance().datagridStore;

    expect(datagridStore.select).toBeCalledWith({id: 1});
    expect(datagridStore.select).toBeCalledWith({id: 2});
    expect(datagridStore.select).toBeCalledWith({id: 3});
});

test('Should not fail when preSelectedItems is undefined', () => {
    const multiDatagridOverlay = shallow(
        <MultiDatagridOverlay
            adapter="table"
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
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(multiDatagridOverlay2.find(DatagridOverlay).prop('adapter')).toEqual('column_list');
});
