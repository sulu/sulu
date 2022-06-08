// @flow
import clipboard from '../clipboard';

test('Set item to localStorage', () => {
    const setItemSpy = jest.fn();
    window.localStorage = {getItem: setItemSpy};

    const item = {key1: 'value1', key2: 1234};
    clipboard.set('test-key', item);
    expect(setItemSpy).toBeCalledWith(item);
});
