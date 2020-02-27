// @flow
import {extendObservable as mockExtendObservable} from 'mobx';
import {shallow} from 'enzyme';
import SelectionFieldFilterType from '../../fieldFilterTypes/SelectionFieldFilterType';

jest.mock('../../../../stores/MultiSelectionStore', () => function() {
    this.loadItems = jest.fn();
    this.items = [];

    mockExtendObservable(this, {
        loading: false,
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
        resourceKey: 'accounts',
        searchProperties: ['name'],
    }));
});

test('Call onChange handler when selection changes', () => {
    const changeSpy = jest.fn();
    const selectionFieldFilterType = new SelectionFieldFilterType(
        changeSpy,
        {displayProperty: 'firstName', resourceKey: 'contacts'},
        undefined
    );

    const selectionFieldFilterTypeForm = shallow(selectionFieldFilterType.getFormNode());

    selectionFieldFilterTypeForm.find('MultiAutoComplete').prop('onChange')([4, 7]);

    expect(changeSpy).toBeCalledWith([4, 7]);
});

test('Return value node without a value', () => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts'},
        undefined
    );

    const valueNodePromise = selectionFieldFilterType.getValueNode(undefined);

    return valueNodePromise.then((valueNode) => {
        expect(selectionFieldFilterType.selectionStore.loadItems).toBeCalledWith([]);
        expect(valueNode).toEqual(null);
    });
});

test('Return value node with a value', (done) => {
    const selectionFieldFilterType = new SelectionFieldFilterType(
        jest.fn(),
        {displayProperty: 'name', resourceKey: 'accounts'},
        undefined
    );

    selectionFieldFilterType.selectionStore.loading = true;
    selectionFieldFilterType.selectionStore.loadItems.mockImplementation(() => {
        selectionFieldFilterType.selectionStore.loading = false;
        selectionFieldFilterType.selectionStore.items = [
            {id: 1, name: 'Max'},
            {id: 2, name: 'Erika'},
            {id: 5, name: 'John'},
        ];
    });

    const valueNodePromise = selectionFieldFilterType.getValueNode([1, 2, 5]);

    setTimeout(() => {
        valueNodePromise.then((valueNode) => {
            expect(selectionFieldFilterType.selectionStore.loadItems).toBeCalledWith([1, 2, 5]);
            expect(valueNode).toEqual('Max, Erika, John');
            done();
        });
    });
});
