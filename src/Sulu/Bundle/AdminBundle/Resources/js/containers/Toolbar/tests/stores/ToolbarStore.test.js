// @flow
import {observable, isObservable, when} from 'mobx';
import ToolbarStore from '../../stores/ToolbarStore';

const toolbarStore = new ToolbarStore();

jest.useFakeTimers();

beforeEach(() => {
    toolbarStore.destroy();
});

test('Set toolbar items and let mobx react', () => {
    const errors = ['You failed!'];

    toolbarStore.setConfig({
        items: [
            {
                icon: 'test',
                label: 'Test',
                onClick: () => {},
                type: 'button',
            },
        ],
        errors,
    });

    const toolbarConfigItems = toolbarStore.config.items;
    if (!toolbarConfigItems) {
        throw new Error('The items should be set now!');
    }

    const buttonConfig = toolbarConfigItems[0];
    if (buttonConfig.type !== 'button') {
        throw new Error('The type should be set now to "button"!');
    }

    expect(isObservable(toolbarStore.config.items)).toBe(true);
    expect(toolbarConfigItems).toHaveLength(1);
    expect(buttonConfig.label).toBe('Test');
    expect(buttonConfig.icon).toBe('test');
    expect(toolbarStore.errors).toEqual(errors);
});

test('Get toolbar errors should return empty array if undefined', () => {
    expect(toolbarStore.errors).toEqual([]);
});

test('Reset showSuccess after 1500ms', () => {
    toolbarStore.setConfig({
        showSuccess: observable.box(true),
    });

    const toolbarConfig = toolbarStore.config;
    if (!toolbarConfig.showSuccess) {
        throw new Error('The showSuccess flag should be set now!');
    }

    expect(toolbarConfig.showSuccess.get()).toEqual(true);

    when(
        () => !!toolbarConfig.showSuccess && toolbarConfig.showSuccess.get() === false,
        (): void => {
            if (!toolbarConfig.showSuccess) {
                throw new Error('The showSuccess flag should be set now!');
            }
            expect(toolbarConfig.showSuccess.get()).toEqual(false);
        }
    );
});
