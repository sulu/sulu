// @flow
import type {ComponentType} from 'react';
import type {
    LinkTypeOptions,
    LinkTypeOverlayProps,
} from '../types';

class linkTypeRegistry {
    overlays: {[string]: ComponentType<LinkTypeOverlayProps>};
    titles: {[string]: string};
    options: {[string]: ?LinkTypeOptions};

    constructor() {
        this.clear();
    }

    clear() {
        this.overlays = {};
        this.titles = {};
        this.options = {};
    }

    add(
        name: string,
        overlay: ComponentType<LinkTypeOverlayProps>,
        title: string,
        options: ?LinkTypeOptions
    ) {
        if (name in this.titles) {
            throw new Error('The key "' + name + '" has already been used for another link type');
        }

        this.overlays[name] = overlay;
        this.titles[name] = title;
        this.options[name] = options;
    }

    getKeys(): Array<string> {
        return Object.keys(this.titles);
    }

    getOverlay(name: string): ComponentType<LinkTypeOverlayProps> {
        if (!(name in this.overlays)) {
            throw new Error(
                'There is no overlay for an link type with the key "' + name + '" registered.' +
                '\n\nRegistered keys: ' + Object.keys(this.overlays).sort().join(', ')
            );
        }

        return this.overlays[name];
    }

    getTitle(name: string): string {
        if (!(name in this.titles)) {
            throw new Error(
                'There is no title for an link type with the key "' + name + '" registered.' +
                '\n\nRegistered keys: ' + Object.keys(this.titles).sort().join(', ')
            );
        }

        return this.titles[name];
    }

    getOptions(name: string): ?LinkTypeOptions {
        if (!(name in this.options)) {
            throw new Error(
                'There are no options for an link type with the key "' + name + '" registered.' +
                '\n\nRegistered keys: ' + Object.keys(this.options).sort().join(', ')
            );
        }

        return this.options[name];
    }
}

export default new linkTypeRegistry();
