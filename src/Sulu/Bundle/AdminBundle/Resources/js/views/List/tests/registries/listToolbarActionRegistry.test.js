// @flow
import listToolbarActionRegistry from '../../registries/listToolbarActionRegistry';
import AbstractListToolbarAction from '../../toolbarActions/AbstractListToolbarAction';

jest.mock('../../../../services/initializer', () => jest.fn());
jest.mock('../../toolbarActions/DeleteToolbarAction', () => jest.fn());

beforeEach(() => {
    listToolbarActionRegistry.clear();
});

test('Clear all toolbar actions', () => {
    listToolbarActionRegistry.add('test1', AbstractListToolbarAction);
    expect(Object.keys(listToolbarActionRegistry.toolbarActions)).toHaveLength(1);

    listToolbarActionRegistry.clear();
    expect(Object.keys(listToolbarActionRegistry.toolbarActions)).toHaveLength(0);
});

test('Add toolbar action', () => {
    listToolbarActionRegistry.add('test1', AbstractListToolbarAction);
    listToolbarActionRegistry.add('test2', AbstractListToolbarAction);

    expect(listToolbarActionRegistry.get('test1')).toBe(AbstractListToolbarAction);
    expect(listToolbarActionRegistry.get('test2')).toBe(AbstractListToolbarAction);
});

test('Add toolbar action with existing key should throw', () => {
    listToolbarActionRegistry.add('test1', AbstractListToolbarAction);
    expect(() => listToolbarActionRegistry.add('test1', AbstractListToolbarAction)).toThrow(/test1/);
});

test('Get toolbar action of not existing key', () => {
    expect(() => listToolbarActionRegistry.get('XXX')).toThrow();
});
