// @flow
import clipboardStore from '../clipboardStore';

test('Load item from localStorage', () => {
    const item = {key1: 'value1', key2: 1234};
    const getItemSpy = jest.fn().mockReturnValue(JSON.stringify(item));
    window.localStorage = {getItem: getItemSpy};

    const result = clipboardStore.get('test-key');
    expect(result).toEqual(item);
    expect(getItemSpy).toBeCalledWith('test-key');
});
