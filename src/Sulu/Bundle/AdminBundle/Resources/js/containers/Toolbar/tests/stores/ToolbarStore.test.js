/* eslint-disable flowtype/require-valid-file-annotation */
import {isObservable} from 'mobx';
import toolbarStore from '../../stores/ToolbarStore';

beforeEach(() => {
    toolbarStore.clearItems();
});

test('Set toolbar items and let mobx react', () => {
    toolbarStore.setItems([{title: 'Test', icon: 'test'}]);

    expect(isObservable(toolbarStore.items)).toBe(true);
    expect(toolbarStore.items).toHaveLength(1);
    expect(toolbarStore.items[0].title).toBe('Test');
    expect(toolbarStore.items[0].icon).toBe('test');
});
