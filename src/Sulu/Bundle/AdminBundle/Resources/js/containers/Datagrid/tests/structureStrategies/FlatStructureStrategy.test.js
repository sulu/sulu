// @flow
import {toJS} from 'mobx';
import FlatStructureStrategy from '../../structureStrategies/FlatStructureStrategy';

test('Should be empty after intialization', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    expect(toJS(flatStructureStrategy.data)).toEqual([]);
});

test('Should return the array on a getData call', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    flatStructureStrategy.data = [{id: 1}];
    expect(flatStructureStrategy.getData()).toBe(flatStructureStrategy.data);
});

test('Should be empty after clear was called', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    flatStructureStrategy.data = [{id: 1}];

    flatStructureStrategy.clear();
    expect(toJS(flatStructureStrategy.data)).toEqual([]);
});

test('Should not enhance the items', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    expect(flatStructureStrategy.enhanceItem({id: 1})).toEqual({id: 1});
});

test('Should return all current items as visible items', () => {
    const flatStructureStrategy = new FlatStructureStrategy();

    const item1 = {
        id: 1,
        title: 'Homepage',
    };

    const item2 = {
        id: 2,
        title: 'Item 2',
    };

    const item3 = {
        id: 'string',
        title: 'Item 3',
    };

    flatStructureStrategy.data = [
        item1,
        item2,
        item3,
    ];

    expect(flatStructureStrategy.visibleItems).toEqual([
        item1,
        item2,
        item3,
    ]);
});

test('Should find items by id or return undefined', () => {
    const flatStructureStrategy = new FlatStructureStrategy();

    const item1 = {
        id: 1,
        title: 'Homepage',
    };

    const item2 = {
        id: 2,
        title: 'Item 2',
    };

    const item3 = {
        id: 'string',
        title: 'Item 3',
    };

    flatStructureStrategy.data = [
        item1,
        item2,
        item3,
    ];

    expect(flatStructureStrategy.findById(1)).toEqual(item1);
    expect(flatStructureStrategy.findById(2)).toEqual(item2);
    expect(flatStructureStrategy.findById('string')).toEqual(item3);
    expect(flatStructureStrategy.findById(4)).toEqual(undefined);
});

test('Should remove an item by id', () => {
    const flatStructureStrategy = new FlatStructureStrategy();

    const item1 = {
        id: 1,
        title: 'Homepage',
    };

    const item2 = {
        id: 2,
        title: 'Item 2',
    };

    flatStructureStrategy.data = [
        item1,
        item2,
    ];

    flatStructureStrategy.remove(2);

    expect(flatStructureStrategy.findById(1)).toEqual(item1);
    expect(flatStructureStrategy.findById(2)).toEqual(undefined);
});
