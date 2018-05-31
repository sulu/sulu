/* eslint-disable flowtype/require-valid-file-annotation */
import {isObservable} from 'mobx';
import ToolbarStore from '../../stores/ToolbarStore';

const toolbarStore = new ToolbarStore();

beforeEach(() => {
    toolbarStore.clearConfig();
});

test('Set toolbar items and let mobx react', () => {
    const errors = [{}];
    toolbarStore.setConfig({
        items: [
            {
                type: 'button',
                value: 'Test',
                icon: 'test',
                onClick: () => {},
            },
        ],
        errors,
    });

    expect(isObservable(toolbarStore.config.items)).toBe(true);
    expect(toolbarStore.config.items).toHaveLength(1);
    expect(toolbarStore.config.items[0].value).toBe('Test');
    expect(toolbarStore.config.items[0].icon).toBe('test');
    expect(toolbarStore.errors).toEqual(errors);
});

test('Get toolbar errors should return empty array if undefined', () => {
    expect(toolbarStore.errors).toEqual([]);
});
