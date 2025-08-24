// @flow
import type {ComponentType} from 'react';
import type {
    LinkTypeOverlayProps,
} from '../types';

class linkOverlayRegistry {
    overlays: {[string]: ComponentType<LinkTypeOverlayProps>};

    constructor() {
        this.clear();
    }

    clear() {
        this.overlays = {};
    }

    setDefaultOverlay(overlay: ComponentType<LinkTypeOverlayProps>) {
        this.overlays['default'] = overlay;
    }

    add(
        type: string,
        overlay: ComponentType<LinkTypeOverlayProps>
    ) {
        this.overlays[type] = overlay;
    }

    getOverlay(type: string): ComponentType<LinkTypeOverlayProps> {
        if (this.overlays[type]) {
            return this.overlays[type];
        }

        if (this.overlays['default']) {
            return this.overlays['default'];
        }

        throw new Error(
            'There is no overlay for an link type with the key "' + type +
            '" registered and no default overlay is set.' +
            '\n\nRegistered keys: ' + Object.keys(this.overlays).sort().join(', ')
        );
    }
}

export default new linkOverlayRegistry();
