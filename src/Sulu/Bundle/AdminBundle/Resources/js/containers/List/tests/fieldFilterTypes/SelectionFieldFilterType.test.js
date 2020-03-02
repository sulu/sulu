// @flow
import {extendObservable as mockExtendObservable} from 'mobx';
import {shallow} from 'enzyme';
import SelectionFieldFilterType from '../../fieldFilterTypes/SelectionFieldFilterType';

jest.mock('../../../../stores/MultiSelectionStore', () => function() {
    this.loadItems = jest.fn();
    this.getById = jest.fn();

    mockExtendObservable(this, {
        loading: false,
        ids: [],
        items: [],
    });
});

test.each([
    [undefined, 'parameters'],
    [{}, 'resourceKey'],
    [{resourceKey: 35}, 'resourceKey'],
])('Throw error if "%s" is passed as a parameter', (parameters, errorMessage) => {
    expect(() => new SelectionFieldFilterType(jest.fn(), parameters, undefined)).toThrow(errorMessage);
});

test('Pass correct props to MultiAutoComplete', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts'},
        undefined
    );

    const selectionFieldFilterTypeForm = shallow(selectionFieldFilterType.getFormNode());

    expect(selectionFieldFilterTypeForm.find('MultiAutoComplete').props()).toEqual(expect.objectContaining({
        displayProperty: 'name',
        searchProperties: ['name'],
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

test('Call onChange handler when selection changes', () => {
    const changeSpy = jest.fn();
    const selectionFieldFilterType = new SelectionFieldFilterType(
        changeSpy,
        {displayProperty: 'firstName', resourceKey: 'contacts'},
        undefined
    );

    selectionFieldFilterType.selectionStore.ids.push(4, 7);

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
