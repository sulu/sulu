// @flow
import type {ComponentType} from 'react';
import type {InternalLinkTypeOptions, InternalLinkTypeOverlayProps} from '../types';

class InternalLinkTypeRegistry {
    overlays: {[string]: ComponentType<InternalLinkTypeOverlayProps>};
    titles: {[string]: string};
    options: {[string]: ?InternalLinkTypeOptions};

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
        overlay: ComponentType<InternalLinkTypeOverlayProps>,
        title: string,
        options: ?InternalLinkTypeOptions
    ) {
        if (name in this.titles) {
            throw new Error('The key "' + name + '" has already been used for another internal link type');
        }

        this.overlays[name] = overlay;
        this.titles[name] = title;
        this.options[name] = options;
    }

    getKeys(): Array<string> {
        return Object.keys(this.titles);
    }

    getOverlay(name: string): ComponentType<InternalLinkTypeOverlayProps> {
        if (!(name in this.overlays)) {
            throw new Error('There is no overlay for an internal link type with the key "' + name + '" registered');
        }

        return this.overlays[name];
    }

    getTitle(name: string): string {
        if (!(name in this.titles)) {
            throw new Error('There is no title for an internal link type with the key "' + name + '" registered');
        }

        return this.titles[name];
    }

    getOptions(name: string): ?InternalLinkTypeOptions {
        if (!(name in this.options)) {
            throw new Error('There are no options for an internal link type with the key "' + name + '" registered');
        }

        return this.options[name];
    }
}

export default new InternalLinkTypeRegistry();
