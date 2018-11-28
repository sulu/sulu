// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import SingleDatagridOverlay from '../../../containers/SingleDatagridOverlay';
import SingleSelection from '../SingleSelection';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/SingleDatagridOverlay', () => jest.fn(function() {
    return <div />;
}));

jest.mock('../../../containers/Datagrid/stores/DatagridStore', () => jest.fn(function() {}));

jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function() {
    this.set = jest.fn((item) => {
        this.item = item;
    });
    this.loadItem = jest.fn((id) => {
        this.item = {id};
    });
    this.clear = jest.fn();

    mockExtendObservable(this, {
        item: undefined,
    });
}));

test('Show with passed emptyText and icon', () => {
    expect(render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Test"
            icon="su-document"
            onChange={jest.fn()}
            overlayTitle=""
            resourceKey="test"
            value={undefined}
        />
    )).toMatchSnapshot();
});

test('Render with selected item', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('test', 3, locale);

    singleSelection.instance().singleSelectionStore.item = {
        id: 3,
        name: 'Name',
        value: 'Value',
    };

    singleSelection.update();

    expect(singleSelection.render()).toMatchSnapshot();
});

test('Render with selected item in disabled state', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabled={true}
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        id: 3,
        name: 'Name',
        value: 'Value',
    };
    singleSelection.update();

    expect(singleSelection.render()).toMatchSnapshot();
});

test('Pass resourceKey and locale to SingleDatagridOverlay', () => {
    const locale = observable.box('en');
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleDatagridOverlay).prop('locale')).toEqual(locale);
    expect(singleSelection.find(SingleDatagridOverlay).prop('resourceKey')).toEqual('test');
});

test('Pass disabledIds to SingleDatagridOverlay', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabledIds={[1, 2, 3]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleDatagridOverlay).prop('disabledIds')).toEqual([1, 2, 3]);
});

test('Should open and close an overlay', () => {
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.find('.button').prop('onClick')();
    singleSelection.update();
    expect(singleSelection.find(SingleDatagridOverlay).prop('open')).toEqual(true);

    singleSelection.find(SingleDatagridOverlay).prop('onClose')();
    singleSelection.update();
    expect(singleSelection.find(SingleDatagridOverlay).prop('open')).toEqual(false);
});

test('Should not open an overlay on button-click when disabled', () => {
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabled={true}
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleDatagridOverlay).prop('open')).toEqual(false);

    singleSelection.find('.button').simulate('click');
    singleSelection.update();
    expect(singleSelection.find(SingleDatagridOverlay).prop('open')).toEqual(false);
});

test('Should call the onChange callback if a new item was selected', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.find('.button').prop('onClick')();
    singleSelection.update();
    expect(singleSelection.find(SingleDatagridOverlay).prop('open')).toEqual(true);

    singleSelection.find(SingleDatagridOverlay).prop('onConfirm')({id: 6});
    expect(singleSelection.instance().singleSelectionStore.loadItem).toBeCalledWith(6);
    expect(changeSpy).toBeCalledWith(6);
    singleSelection.update();
    expect(singleSelection.find(SingleDatagridOverlay).prop('open')).toEqual(false);
});

test('Should not call the onChange callback if the same item was selected', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={6}
        />
    );

    singleSelection.find(SingleDatagridOverlay).prop('onConfirm')({id: 6});
    expect(changeSpy).not.toBeCalled();
});

test('Should load the item if value prop changes', () => {
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    singleSelection.setProps({value: 3});
    expect(singleSelection.instance().singleSelectionStore.loadItem).toBeCalledWith(3);
});

test('Should remove an item when the remove button is clicked', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        name: 'Name',
        value: 'Value',
    };
    singleSelection.find('SingleItemSelection').prop('onRemove')();
    expect(singleSelection.instance().singleSelectionStore.clear).toBeCalledWith();
});

test('Should call the onChange callback if the value of the selection-store changes', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {id: 6};
    expect(changeSpy).toBeCalledWith(6);
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.setProps({emptyText: 'New Empty Text'});
    expect(changeSpy).not.toBeCalled();
});
