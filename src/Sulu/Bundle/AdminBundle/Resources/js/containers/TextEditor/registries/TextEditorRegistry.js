// @flow
import type {ComponentType} from 'react';
import type {TextEditorProps} from '../types';

class TextEditorRegistry {
    textEditors: {[string]: ComponentType<TextEditorProps>};

    constructor() {
        this.clear();
    }

    clear() {
        this.textEditors = {};
    }

    has(name: string) {
        return name in this.textEditors;
    }

    add(name: string, textEditor: ComponentType<TextEditorProps>) {
        if (this.has(name)) {
            throw new Error('The key "' + name + '" has already been used for another TextEditor');
        }

        this.textEditors[name] = textEditor;
    }

    get(name: string) {
        if (!this.has(name)) {
            throw new Error('There is no TextEditor with key "' + name + '" registered');
        }

        return this.textEditors[name];
    }
}

export default new TextEditorRegistry();
