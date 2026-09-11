// @flow
import type {TextEditorConfig} from '../types';

class TextEditorConfigRegistry {
    configs: {[string]: TextEditorConfig};

    constructor() {
        this.clear();
    }

    clear() {
        // A null prototype, so that a config named "constructor" or "__proto__" behaves like any other key.
        // $FlowFixMe: flow describes a null prototype as incompatible with an object type
        this.configs = Object.create(null);
    }

    has(name: string) {
        return name in this.configs;
    }

    add(name: string, config: TextEditorConfig) {
        if (this.has(name)) {
            throw new Error('The key "' + name + '" has already been used for another TextEditor config');
        }

        this.configs[name] = config;
    }

    get(name: string): TextEditorConfig {
        if (!this.has(name)) {
            throw new Error(
                'There is no TextEditor config with the key "' + name + '" registered.' +
                '\n\nRegistered keys: ' + Object.keys(this.configs).sort().join(', ')
            );
        }

        return this.configs[name];
    }
}

export default new TextEditorConfigRegistry();
