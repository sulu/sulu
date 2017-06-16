/* eslint-disable flowtype/require-valid-file-annotation */
import {addView, getView} from '../ViewRegistry';

test('Add view to ViewRegistry', () => {
    addView('test1', 'test1 react component');
    addView('test2', 'test2 react component');

    expect(getView('test1')).toBe('test1 react component');
    expect(getView('test2')).toBe('test2 react component');
});
