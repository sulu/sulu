// @flow
import toolbarActionRegistry from '../../registries/ToolbarActionRegistry';
import AbstractToolbarAction from '../../toolbarActions/AbstractToolbarAction';

jest.mock('../../../../services/Initializer', () => jest.fn());
jest.mock('../../toolbarActions/DeleteToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/SaveWithPublishingToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/SaveToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/TypeToolbarAction', () => jest.fn());

beforeEach(() => {
    toolbarActionRegistry.clear();
});

test('Clear all adapters', () => {
    toolbarActionRegistry.add('test1', AbstractToolbarAction);
    expect(Object.keys(toolbarActionRegistry.toolbarActions)).toHaveLength(1);

    toolbarActionRegistry.clear();
    expect(Object.keys(toolbarActionRegistry.toolbarActions)).toHaveLength(0);
});

test('Add adapter', () => {
    toolbarActionRegistry.add('test1', AbstractToolbarAction);
    toolbarActionRegistry.add('test2', AbstractToolbarAction);

    expect(toolbarActionRegistry.get('test1')).toBe(AbstractToolbarAction);
    expect(toolbarActionRegistry.get('test2')).toBe(AbstractToolbarAction);
});

test('Add adapter with existing key should throw', () => {
    toolbarActionRegistry.add('test1', AbstractToolbarAction);
    expect(() => toolbarActionRegistry.add('test1', AbstractToolbarAction)).toThrow(/test1/);
});

test('Get adapter of not existing key', () => {
    expect(() => toolbarActionRegistry.get('XXX')).toThrow();
});
