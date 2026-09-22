// @flow
import viewToolbarActionRegistry from '../../registries/viewToolbarActionRegistry';
import AbstractViewToolbarAction from '../../toolbarActions/AbstractViewToolbarAction';

beforeEach(() => {
    viewToolbarActionRegistry.clear();
});

test('Clear all toolbar actions', () => {
    viewToolbarActionRegistry.add('test1', AbstractViewToolbarAction);
    expect(Object.keys(viewToolbarActionRegistry.toolbarActions)).toHaveLength(1);

    viewToolbarActionRegistry.clear();
    expect(Object.keys(viewToolbarActionRegistry.toolbarActions)).toHaveLength(0);
});

test('Add toolbar action', () => {
    viewToolbarActionRegistry.add('test1', AbstractViewToolbarAction);
    viewToolbarActionRegistry.add('test2', AbstractViewToolbarAction);

    expect(viewToolbarActionRegistry.get('test1')).toBe(AbstractViewToolbarAction);
    expect(viewToolbarActionRegistry.get('test2')).toBe(AbstractViewToolbarAction);
});

test('Add toolbar action with existing key should throw', () => {
    viewToolbarActionRegistry.add('test1', AbstractViewToolbarAction);
    expect(() => viewToolbarActionRegistry.add('test1', AbstractViewToolbarAction)).toThrow(/test1/);
});

test('Get toolbar action of not existing key', () => {
    expect(() => viewToolbarActionRegistry.get('XXX')).toThrow();
});
