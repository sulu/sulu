// @flow
import React from 'react';
import textEditorRegistry from '../../registries/TextEditorRegistry';
import type {TextEditorProps} from '../../types';

class TextEditor extends React.Component<TextEditorProps> {

}

beforeEach(() => {
    textEditorRegistry.clear();
});

test('Clear all text editors', () => {
    textEditorRegistry.add('test1', TextEditor);
    expect(Object.keys(textEditorRegistry.textEditors)).toHaveLength(1);

    textEditorRegistry.clear();
    expect(Object.keys(textEditorRegistry.textEditors)).toHaveLength(0);
});

test('Add text editors to the registry', () => {
    textEditorRegistry.add('test1', TextEditor);

    expect(textEditorRegistry.get('test1')).toBe(TextEditor);
});

test('Add text editor with already existing key should throw', () => {
    textEditorRegistry.add('test1', TextEditor);
    expect(() => textEditorRegistry.add('test1', TextEditor)).toThrow(/test1/);
});

test('Get text editor for not existing key should throw', () => {
    expect(() => textEditorRegistry.get('test1')).toThrow(/test1/);
});

test('Has should return true if a key exists', () => {
    textEditorRegistry.add('test1', TextEditor);
    expect(textEditorRegistry.has('test1')).toEqual(true);
});

test('Has should return false if a key does not exist', () => {
    expect(textEditorRegistry.has('test')).toEqual(false);
});
