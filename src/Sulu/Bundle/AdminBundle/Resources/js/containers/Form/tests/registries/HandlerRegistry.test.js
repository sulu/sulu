// @flow
import handlerRegistry from '../../registries/HandlerRegistry';

beforeEach(() => {
    handlerRegistry.clear();
});

test('Clear all handlers from registry', () => {
    handlerRegistry.addFinishFieldHandler(jest.fn());
    expect(handlerRegistry.finishFieldHandlers).toHaveLength(1);
    handlerRegistry.clear();
    expect(handlerRegistry.finishFieldHandlers).toHaveLength(0);
});

test('Should return all added finishHandlers', () => {
    const handler1 = jest.fn();
    const handler2 = jest.fn();

    handlerRegistry.addFinishFieldHandler(handler1);
    handlerRegistry.addFinishFieldHandler(handler2);

    expect(handlerRegistry.getFinishFieldHandlers()).toEqual([handler1, handler2]);
});
