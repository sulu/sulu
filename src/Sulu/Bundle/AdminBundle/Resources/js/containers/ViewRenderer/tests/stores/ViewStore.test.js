/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import originalViewStore from '../../stores/ViewStore';

test('Add view to ViewRegistry', () => {
    const viewStore = Object.create(originalViewStore);
    viewStore.map = {};

    const component1 = () => (<h1>Test1</h1>);
    const component2 = () => (<h1>Test2</h1>);
    viewStore.add('test1', component1);
    viewStore.add('test2', component2);

    expect(viewStore.get('test1')).toBe(component1);
    expect(viewStore.get('test2')).toBe(component2);
});

test('Add view with existing key should throw', () => {
    const viewStore = Object.create(originalViewStore);
    viewStore.map = {};

    const component1 = () => (<h1>Test1</h1>);
    viewStore.add('test1', component1);
    expect(() => viewStore.add('test1', 'test1 react component')).toThrow(/test1/);
});
