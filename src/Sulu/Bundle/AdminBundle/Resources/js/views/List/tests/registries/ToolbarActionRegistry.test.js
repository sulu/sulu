// @flow
import toolbarActionRegistry from '../../registries/ToolbarActionRegistry';
import AbstractListToolbarAction from '../../toolbarActions/AbstractListToolbarAction';

jest.mock('../../../../services/Initializer', () => jest.fn());
jest.mock('../../toolbarActions/DeleteToolbarAction', () => jest.fn());

beforeEach(() => {
    toolbarActionRegistry.clear();
});

test('Clear all toolbar actions', () => {
    toolbarActionRegistry.add('test1', AbstractListToolbarAction);
    expect(Object.keys(toolbarActionRegistry.toolbarActions)).toHaveLength(1);

    toolbarActionRegistry.clear();
    expect(Object.keys(toolbarActionRegistry.toolbarActions)).toHaveLength(0);
});

test('Add adapter', () => {
    toolbarActionRegistry.add('test1', AbstractListToolbarAction);
    toolbarActionRegistry.add('test2', AbstractListToolbarAction);

    expect(toolbarActionRegistry.get('test1')).toBe(AbstractListToolbarAction);
    expect(toolbarActionRegistry.get('test2')).toBe(AbstractListToolbarAction);
});

test('Add adapter with existing key should throw', () => {
    toolbarActionRegistry.add('test1', AbstractListToolbarAction);
    expect(() => toolbarActionRegistry.add('test1', AbstractListToolbarAction)).toThrow(/test1/);
});

test('Get adapter of not existing key', () => {
    expect(() => toolbarActionRegistry.get('XXX')).toThrow();
});
