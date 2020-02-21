// @flow
import listItemActionRegistry from '../../registries/listItemActionRegistry';
import AbstractListItemAction from '../../itemActions/AbstractListItemAction';

beforeEach(() => {
    listItemActionRegistry.clear();
});

test('Clear all item actions', () => {
    listItemActionRegistry.add('test1', AbstractListItemAction);
    expect(Object.keys(listItemActionRegistry.listItemActions)).toHaveLength(1);

    listItemActionRegistry.clear();
    expect(Object.keys(listItemActionRegistry.listItemActions)).toHaveLength(0);
});

test('Add item action', () => {
    listItemActionRegistry.add('test1', AbstractListItemAction);
    listItemActionRegistry.add('test2', AbstractListItemAction);

    expect(listItemActionRegistry.get('test1')).toBe(AbstractListItemAction);
    expect(listItemActionRegistry.get('test2')).toBe(AbstractListItemAction);
});

test('Add item action with existing key should throw', () => {
    listItemActionRegistry.add('test1', AbstractListItemAction);
    expect(() => listItemActionRegistry.add('test1', AbstractListItemAction)).toThrow(/test1/);
});

test('Get item action of not existing key', () => {
    expect(() => listItemActionRegistry.get('XXX')).toThrow();
});
