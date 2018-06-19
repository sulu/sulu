// @flow
import ColumnStructureStrategy from '../../structureStrategies/ColumnStructureStrategy';

test('Should return the active items', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(0, []);
    columnStructureStrategy.rawData.set(1, []);

    expect(columnStructureStrategy.activeItems).toEqual([undefined, 0, 1]);
});

test('Should return the data in a column format', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}]);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1}],
        [{id: 2}],
        [{id: 3}],
    ]);
});

test('Should return the visible data', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}, {id: 3}]);
    columnStructureStrategy.rawData.set(2, [{id: 4}]);

    expect(columnStructureStrategy.visibleData).toEqual([
        {id: 1},
        {id: 2},
        {id: 3},
        {id: 4},
    ]);
});

test('Should return the column for a given parent in getData', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    const column1 = [{id: 1}];
    columnStructureStrategy.rawData.set(undefined, column1);
    const column2 = [{id: 2}];
    columnStructureStrategy.rawData.set(1, column2);
    const column3 = [{id: 3}];
    columnStructureStrategy.rawData.set(2, column3);

    expect(columnStructureStrategy.getData(1)).toEqual([{id: 2}]);
});

test('Should remove the columns after the activated item', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    const column1 = [{id: 1}];
    columnStructureStrategy.rawData.set(undefined, column1);
    const column2 = [{id: 2}];
    columnStructureStrategy.rawData.set(1, column2);
    const column3 = [{id: 3}];
    columnStructureStrategy.rawData.set(2, column3);

    columnStructureStrategy.activate(2);
    expect(columnStructureStrategy.data).toEqual([
        [{id: 1}],
        [{id: 2}],
        [],
    ]);
});

test('Should return a item by id', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}, {id: 4}]);
    columnStructureStrategy.rawData.set(3, [{id: 5}, {id: 6}]);

    expect(columnStructureStrategy.findById(4)).toEqual({id: 4});
});

test('Should return undefined if item with given id does not exist', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}, {id: 4}]);
    columnStructureStrategy.rawData.set(3, [{id: 5}, {id: 6}]);

    expect(columnStructureStrategy.findById(7)).toEqual(undefined);
});

test('Should be empty after clear was called', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData = new Map();
    columnStructureStrategy.rawData.set(0, []);
    columnStructureStrategy.rawData.set(1, []);

    expect(columnStructureStrategy.data).toHaveLength(2);
    columnStructureStrategy.clear();
    expect(columnStructureStrategy.data).toHaveLength(1);
    expect(columnStructureStrategy.getData(undefined)).toEqual([]);
});

test('Should not enhance the items', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    expect(columnStructureStrategy.enhanceItem({id: 1})).toEqual({id: 1});
});

test('Should return the data in a column format', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}, {id: 4}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}, {id: 5}]);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1}],
        [{id: 2}, {id: 4}],
        [{id: 3}, {id: 5}],
    ]);

    columnStructureStrategy.remove(3);
    columnStructureStrategy.remove(4);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1}],
        [{id: 2}],
        [{id: 5}],
    ]);
});
