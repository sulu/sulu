// @flow
import type {BlockPreviewTransformer} from '../types';

class BlockPreviewTransformerRegistry {
    blockPreviewTransformers: {[string]: BlockPreviewTransformer};

    constructor() {
        this.clear();
    }

    clear() {
        this.blockPreviewTransformers = {};
    }

    has(name: string) {
        return !!this.blockPreviewTransformers[name];
    }

    add(name: string, blockPreviewTransformer: BlockPreviewTransformer) {
        if (name in this.blockPreviewTransformers) {
            throw new Error('The key "' + name + '" has already been used for another BlockPreviewTransformer');
        }

        this.blockPreviewTransformers[name] = blockPreviewTransformer;
    }

    get(name: string): BlockPreviewTransformer {
        if (!(name in this.blockPreviewTransformers)) {
            throw new Error(
                'The BlockPreviewTransformer with the key "' + name + '" is not defined. ' +
                'You probably forgot to add it to the registry using the "add" method.'
            );
        }

        return this.blockPreviewTransformers[name];
    }
}

export default new BlockPreviewTransformerRegistry();
