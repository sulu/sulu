// @flow
import formToolbarActionRegistry from '../../registries/formToolbarActionRegistry';
import AbstractFormToolbarAction from '../../toolbarActions/AbstractFormToolbarAction';

jest.mock('../../../../services/initializer', () => jest.fn());
jest.mock('../../toolbarActions/DeleteToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/SaveWithPublishingToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/SaveToolbarAction', () => jest.fn());
jest.mock('../../toolbarActions/TypeToolbarAction', () => jest.fn());

beforeEach(() => {
    formToolbarActionRegistry.clear();
});

test('Clear all toolbar actions', () => {
    formToolbarActionRegistry.add('test1', AbstractFormToolbarAction);
    expect(Object.keys(formToolbarActionRegistry.toolbarActions)).toHaveLength(1);

    formToolbarActionRegistry.clear();
    expect(Object.keys(formToolbarActionRegistry.toolbarActions)).toHaveLength(0);
});

test('Add adapter', () => {
    formToolbarActionRegistry.add('test1', AbstractFormToolbarAction);
    formToolbarActionRegistry.add('test2', AbstractFormToolbarAction);

    expect(formToolbarActionRegistry.get('test1')).toBe(AbstractFormToolbarAction);
    expect(formToolbarActionRegistry.get('test2')).toBe(AbstractFormToolbarAction);
});

test('Add adapter with existing key should throw', () => {
    formToolbarActionRegistry.add('test1', AbstractFormToolbarAction);
    expect(() => formToolbarActionRegistry.add('test1', AbstractFormToolbarAction)).toThrow(/test1/);
});

test('Get adapter of not existing key', () => {
    expect(() => formToolbarActionRegistry.get('XXX')).toThrow();
});
