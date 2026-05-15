// @flow
import {render} from '@testing-library/react';
import {extendObservable as mockExtendObservable} from 'mobx';
import MultiAutoComplete from '../../../MultiAutoComplete';
import ResourceCheckboxGroup from '../../../ResourceCheckboxGroup';
import SelectionFieldFilterType from '../../fieldFilterTypes/SelectionFieldFilterType';
import MultiSelectionStore from '../../../../stores/MultiSelectionStore';
import userStore from '../../../../stores/userStore';

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(function() {
    this.loadItems = jest.fn();
    // eslint-disable-next-line testing-library/prefer-explicit-assert
    this.getById = jest.fn();

    mockExtendObservable(this, {
        loading: false,
        ids: [],
        items: [],
    });
}));

jest.mock('../../../../stores/userStore', () => ({}));
jest.mock('../../../MultiAutoComplete', () => jest.fn(() => null));
jest.mock('../../../ResourceCheckboxGroup', () => jest.fn(() => null));

function getLatestMultiAutoCompleteProps() {
    const calls = (MultiAutoComplete: any).mock.calls;
    return calls[calls.length - 1][0];
}

function getLatestResourceCheckboxGroupProps() {
    const calls = (ResourceCheckboxGroup: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test.each([
    [undefined, 'parameters'],
    [{}, 'resourceKey'],
    [{resourceKey: 35}, 'resourceKey'],
])('Throw error if "%s" is passed as a parameter', (parameters, errorMessage) => {
    expect(() => new SelectionFieldFilterType(jest.fn(), parameters, undefined)).toThrow(errorMessage);
});

test('Pass correct props to MultiAutoComplete', () => {
    // $FlowFixMe
    userStore.contentLocale = 'ru';

    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts', requestParameters: {rootKey: 'rootKey'}},
        undefined
    );

    expect(MultiSelectionStore).toBeCalledWith('accounts', [], expect.anything(), 'ids', {rootKey: 'rootKey'});
    // $FlowFixMe
    expect(MultiSelectionStore.mock.calls[0][2].get()).toEqual('ru');

    render(selectionFieldFilterType.getFormNode());

    expect(getLatestMultiAutoCompleteProps()).toEqual(expect.objectContaining({
        displayProperty: 'name',
        searchProperties: ['name'],
        selectionStore: selectionFieldFilterType.selectionStore,
    }));
});

test('Pass correct props to Select', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts', type: 'select'},
        [4, 6]
    );

    render(selectionFieldFilterType.getFormNode());

    expect(getLatestResourceCheckboxGroupProps()).toEqual(expect.objectContaining({
        displayProperty: 'name',
        resourceKey: 'accounts',
        values: [4, 6],
    }));
});

test('Destroy should call disposers', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts'},
        undefined
    );

    selectionFieldFilterType.selectionStoreDisposer = jest.fn();
    selectionFieldFilterType.valueDisposer = jest.fn();

    selectionFieldFilterType.destroy();

    expect(selectionFieldFilterType.selectionStoreDisposer).toBeCalledWith();
    expect(selectionFieldFilterType.valueDisposer).toBeCalledWith();
});

test('Setting a new value should update the select', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts', type: 'select'},
        [4, 6]
    );

    render(selectionFieldFilterType.getFormNode());
    expect(getLatestResourceCheckboxGroupProps().values).toEqual([4, 6]);

    selectionFieldFilterType.setValue([4, 5]);
    render(selectionFieldFilterType.getFormNode());
    expect(getLatestResourceCheckboxGroupProps().values).toEqual([4, 5]);
});

test('Setting a new value should update the selectionStore', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts', type: 'select'},
        [4, 6]
    );

    expect(selectionFieldFilterType.selectionStore.loadItems).toBeCalledWith([4, 6]);
    selectionFieldFilterType.setValue([4, 7]);
    expect(selectionFieldFilterType.selectionStore.loadItems).toBeCalledWith([4, 7]);
});

test('Setting the same value should not update the selectionStore', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts', type: 'select'},
        [4, 6]
    );

    // $FlowFixMe
    selectionFieldFilterType.selectionStore.ids = [4, 6];
    selectionFieldFilterType.selectionStore.loadItems.mockReset();

    selectionFieldFilterType.setValue([4, 6]);
    expect(selectionFieldFilterType.selectionStore.loadItems).not.toBeCalledWith([4, 6]);
});

test('Call onChange handler when selection changes for auto_complete type', () => {
    const changeSpy = jest.fn();
    const selectionFieldFilterType = new SelectionFieldFilterType(
        changeSpy,
        {displayProperty: 'firstName', resourceKey: 'contacts', type: 'multi_auto_complete'},
        undefined
    );

    selectionFieldFilterType.selectionStore.ids.push(4, 7);

    expect(changeSpy).toBeCalledWith([4, 7]);
});

test('Call onChange handler when selection changes for select type after filter type is confirmed', () => {
    const changeSpy = jest.fn();
    const selectionFieldFilterType = new SelectionFieldFilterType(
        changeSpy,
        {displayProperty: 'firstName', resourceKey: 'contacts', type: 'select'},
        undefined
    );

    render(selectionFieldFilterType.getFormNode());
    changeSpy.mockReset();
    getLatestResourceCheckboxGroupProps().onChange([4, 7]);

    expect(changeSpy).not.toBeCalled();
    selectionFieldFilterType.confirm();
    expect(changeSpy).toBeCalledWith([4, 7]);
});

test('Return value node without a value', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts'},
        undefined
    );

    selectionFieldFilterType.selectionStore.loadItems.mockReset();
    const valueNodePromise = selectionFieldFilterType.getValueNode(undefined);

    return valueNodePromise.then((valueNode) => {
        expect(selectionFieldFilterType.selectionStore.loadItems).not.toBeCalled();
        expect(valueNode).toEqual(null);
    });
});

test('Return value node with a value', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts'},
        undefined
    );

    selectionFieldFilterType.selectionStore.loadItems.mockReset();

    selectionFieldFilterType.selectionStore.loading = false;
    selectionFieldFilterType.selectionStore.getById.mockImplementation(function(id) {
        switch (id) {
            case 1:
                return {id: 1, name: 'Max'};
            case 2:
                return {id: 2, name: 'Erika'};
            case 5:
                return {id: 5, name: 'John'};
        }
    });

    const valueNodePromise = selectionFieldFilterType.getValueNode([1, 2, 5]);

    return valueNodePromise.then((valueNode) => {
        expect(selectionFieldFilterType.selectionStore.loadItems).not.toBeCalled();
        expect(valueNode).toEqual('Max, Erika, John');
    });
});
