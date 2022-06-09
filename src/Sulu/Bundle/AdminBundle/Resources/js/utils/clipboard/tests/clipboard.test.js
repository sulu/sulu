// @flow
import clipboard from '../clipboard';

test('Set item and observe item', () => {
    clipboard.set('test-key', 'test-value');

    let item = null;

    clipboard.observe('test-key', (value) => {
        item = value;
    }, true);

    expect(item).toEqual('test-value');
});
