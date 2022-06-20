// @flow
import clipboard from '../clipboard';

test('Set should store data in localStorage', () => {
    const setItemSpy = jest.spyOn(Storage.prototype, 'setItem');

    expect(setItemSpy).not.toBeCalled();
    clipboard.set('test-key', {objectKey1: 'object-value-1'});
    expect(setItemSpy).toBeCalledWith('test-key', JSON.stringify({objectKey1: 'object-value-1'}));
});

test('Set should remove data in localStorage when passing falsy value', () => {
    const removeItemSpy = jest.spyOn(Storage.prototype, 'removeItem');

    expect(removeItemSpy).not.toBeCalled();
    clipboard.set('test-key', undefined);
    expect(removeItemSpy).toBeCalledWith('test-key');
});

test('Observer should be called with current value if invokeImmediately parameter is set', () => {
    clipboard.set('test-key', 'current-test-value');

    const observerSpy = jest.fn();
    clipboard.observe('test-key', observerSpy, true);

    expect(observerSpy).toBeCalledWith('current-test-value');
});

test('Observer should be called when observed key is changed via service', () => {
    const observerSpy = jest.fn();
    const disposer = clipboard.observe('test-key', observerSpy);

    expect(observerSpy).not.toBeCalled();

    clipboard.set('test-key', 'test-value-1');
    expect(observerSpy).toBeCalledWith('test-value-1');
    expect(observerSpy).toBeCalledTimes(1);

    clipboard.set('test-key', 'test-value-2');
    expect(observerSpy).toBeCalledWith('test-value-2');
    expect(observerSpy).toBeCalledTimes(2);

    clipboard.set('other-key', 'test-value-3');
    expect(observerSpy).toBeCalledTimes(2);

    disposer();
    clipboard.set('test-key', 'test-value-4');
    expect(observerSpy).toBeCalledTimes(2);
});

test('Observer should be called when observed key is changed n another tab', () => {
    const observerSpy = jest.fn();
    const disposer = clipboard.observe('test-key', observerSpy);

    expect(observerSpy).not.toBeCalled();

    window.dispatchEvent(new StorageEvent('storage', {key: 'test-key', newValue: JSON.stringify({key1: 'value1'})}));
    expect(observerSpy).toBeCalledWith({key1: 'value1'});
    expect(observerSpy).toBeCalledTimes(1);

    window.dispatchEvent(new StorageEvent('storage', {key: 'test-key', newValue: JSON.stringify({key1: 'value2'})}));
    expect(observerSpy).toBeCalledWith({key1: 'value2'});
    expect(observerSpy).toBeCalledTimes(2);

    window.dispatchEvent(new StorageEvent('storage', {key: 'other-key', newValue: JSON.stringify({key1: 'value3'})}));
    expect(observerSpy).toBeCalledTimes(2);

    disposer();
    window.dispatchEvent(new StorageEvent('storage', {key: 'test-key', newValue: JSON.stringify({key1: 'value4'})}));
    expect(observerSpy).toBeCalledTimes(2);
});
