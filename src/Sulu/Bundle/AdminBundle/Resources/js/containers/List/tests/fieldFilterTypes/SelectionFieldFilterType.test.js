// @flow
import {extendObservable as mockExtendObservable} from 'mobx';
import {shallow} from 'enzyme';
import SelectionFieldFilterType from '../../fieldFilterTypes/SelectionFieldFilterType';
import MultiSelectionStore from '../../../../stores/MultiSelectionStore';
import userStore from '../../../../stores/userStore';

jest.mock('../../../../stores/MultiSelectionStore', () => jest.fn(function() {
    this.loadItems = jest.fn();
    this.getById = jest.fn();

    mockExtendObservable(this, {
        loading: false,
        ids: [],
        items: [],
    });
}));

jest.mock('../../../../stores/userStore', () => ({}));

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

    const selectionFieldFilterTypeForm = shallow(selectionFieldFilterType.getFormNode());

    expect(selectionFieldFilterTypeForm.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
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

    const selectionFieldFilterTypeForm = shallow(selectionFieldFilterType.getFormNode());

    expect(selectionFieldFilterTypeForm.find('ResourceCheckboxGroup').props()).toEqual(expect.objectContaining({
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

    const selectionFieldFilterTypeForm1 = shallow(selectionFieldFilterType.getFormNode());
    expect(selectionFieldFilterTypeForm1.find('ResourceCheckboxGroup').prop('values')).toEqual([4, 6]);

    selectionFieldFilterType.setValue([4, 5]);
    const selectionFieldFilterTypeForm2 = shallow(selectionFieldFilterType.getFormNode());
    expect(selectionFieldFilterTypeForm2.find('ResourceCheckboxGroup').prop('values')).toEqual([4, 5]);
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

    const selectionFieldFilterTypeForm = shallow(selectionFieldFilterType.getFormNode());
    changeSpy.mockReset();
    selectionFieldFilterTypeForm.find('ResourceCheckboxGroup').prop('onChange')([4, 7]);

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

test('Return value node with a value', (done) => {
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
        done();
    });
});
