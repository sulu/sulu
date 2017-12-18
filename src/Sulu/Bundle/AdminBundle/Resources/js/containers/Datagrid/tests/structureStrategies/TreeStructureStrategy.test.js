// @flow
import {toJS} from 'mobx';
import TreeStructureStrategy from '../../structureStrategies/TreeStructureStrategy';

test('Should be empty after intialization', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    expect(toJS(treeStructureStrategy.data.size)).toEqual(0);
});

test('Should return the array on a getData call', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    const data = [
        {id: 4},
        {id: 8},
    ];
    treeStructureStrategy.data.set('some-uuid', data);
    expect(toJS(treeStructureStrategy.getData('some-uuid'))).toEqual(data);
});

test('Should be empty after clear was called', () => {
    const treeStructureStrategy = new TreeStructureStrategy();
    treeStructureStrategy.data.set('test', []);

    treeStructureStrategy.clear();
    expect(toJS(treeStructureStrategy.data.size)).toEqual(0);
});
