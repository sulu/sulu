// @flow
import type {ComponentType} from 'react';
import type {TextEditorProps} from './../types';

class TextEditorRegistry {
    textEditors: {[string]: ComponentType<TextEditorProps>};

    constructor() {
        this.clear();
    }

    clear() {
        this.textEditors = {};
    }

    has(name: string) {
        return !!this.textEditors[name];
    }

    add(name: string, TextEditor: ComponentType<TextEditorProps>) {
        if (name in this.textEditors) {
            throw new Error('The name "' + name + '" has already been used for another text-editor.');
        }

        this.textEditors[name] = TextEditor;
    }

    get(name: string): ComponentType<TextEditorProps> {
        if (!(name in this.textEditors)) {
            throw new Error(
                'The text-editor with the name "' + name + '" is not defined. ' +
                'You probably forgot to add it to the registry using the "add" method.'
            );
        }

        return this.textEditors[name];
    }
}

export default new TextEditorRegistry();
