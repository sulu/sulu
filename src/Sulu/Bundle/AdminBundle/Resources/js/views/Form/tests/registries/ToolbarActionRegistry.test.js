// @flow
import toolbarActionRegistry from '../../registries/ToolbarActionRegistry';
import AbstractFormToolbarAction from '../../toolbarActions/AbstractFormToolbarAction';

jest.mock('../../../../services/Initializer', () => jest.fn());
jest.mock('../../toolbarActions/DeleteToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/SaveWithPublishingToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/SaveToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/TypeToolbarAction', () => jest.fn());

beforeEach(() => {
    toolbarActionRegistry.clear();
});

test('Clear all toolbar actions', () => {
    toolbarActionRegistry.add('test1', AbstractFormToolbarAction);
    expect(Object.keys(toolbarActionRegistry.toolbarActions)).toHaveLength(1);

    toolbarActionRegistry.clear();
    expect(Object.keys(toolbarActionRegistry.toolbarActions)).toHaveLength(0);
});

test('Add adapter', () => {
    toolbarActionRegistry.add('test1', AbstractFormToolbarAction);
    toolbarActionRegistry.add('test2', AbstractFormToolbarAction);

    expect(toolbarActionRegistry.get('test1')).toBe(AbstractFormToolbarAction);
    expect(toolbarActionRegistry.get('test2')).toBe(AbstractFormToolbarAction);
});

test('Add adapter with existing key should throw', () => {
    toolbarActionRegistry.add('test1', AbstractFormToolbarAction);
    expect(() => toolbarActionRegistry.add('test1', AbstractFormToolbarAction)).toThrow(/test1/);
});

test('Get adapter of not existing key', () => {
    expect(() => toolbarActionRegistry.get('XXX')).toThrow();
});
