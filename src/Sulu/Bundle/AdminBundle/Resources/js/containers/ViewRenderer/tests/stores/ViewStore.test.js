/* eslint-disable flowtype/require-valid-file-annotation */
import viewStore from '../../stores/ViewStore';

test('Add view to ViewRegistry', () => {
    viewStore.add('test1', 'test1 react component');
    viewStore.add('test2', 'test2 react component');

    expect(viewStore.get('test1')).toBe('test1 react component');
    expect(viewStore.get('test2')).toBe('test2 react component');
});
