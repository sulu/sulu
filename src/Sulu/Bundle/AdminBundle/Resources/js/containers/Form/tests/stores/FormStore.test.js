/* eslint-disable flowtype/require-valid-file-annotation */
import FormStore from '../../stores/FormStore';

test('Create data object for schema', () => {
    const formStore = new FormStore();
    formStore.changeSchema({
        title: {},
        description: {},
    });

    expect(Object.keys(formStore.data)).toHaveLength(2);
    expect(formStore.data).toEqual({
        title: null,
        description: null,
    });

    formStore.changeSchema({
        text: {},
    });

    expect(Object.keys(formStore.data)).toHaveLength(1);
    expect(formStore.data).toEqual({
        text: null,
    });
});

test('Should be marked dirty when value is changed', () => {
    const formStore = new FormStore();
    expect(formStore.dirty).toBe(false);
    formStore.set('test', 'value');

    expect(formStore.data.test).toBe('value');
    expect(formStore.dirty).toBe(true);
});
