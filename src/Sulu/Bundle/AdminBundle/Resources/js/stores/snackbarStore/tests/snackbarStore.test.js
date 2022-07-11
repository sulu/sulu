// @flow
import snackbarStore from '../snackbarStore';

beforeEach(() => {
    snackbarStore.clear();
});

test('Add should increase the messages', () => {
    expect(snackbarStore.messages.length).toBe(0);

    snackbarStore.add({
        text: 'Test message',
        type: 'success',
    });

    expect(snackbarStore.messages.length).toBe(1);
});

test('Remove should decrease the messages', () => {
    const message = {
        text: 'Test message',
        type: 'success',
    };

    snackbarStore.add(message);
    snackbarStore.remove(message);

    expect(snackbarStore.messages.length).toBe(0);
});

test('Clear should remove all messages', () => {
    const message = {
        text: 'Test message',
        type: 'success',
    };

    snackbarStore.add(message);
    snackbarStore.clear();

    expect(snackbarStore.messages.length).toBe(0);
});

test('Add with message should create a setTimeout', () => {
    // eslint-disable-next-line no-undef
    const timeoutSpy = jest.spyOn(global, 'setTimeout');

    const message = {
        text: 'Test message',
        type: 'success',
    };

    snackbarStore.add(message, 10);

    expect(timeoutSpy).toBeCalled();
});
