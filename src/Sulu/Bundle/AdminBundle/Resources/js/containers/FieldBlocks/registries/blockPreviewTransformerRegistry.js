// @flow
import {computed, observable} from 'mobx';
import type {BlockPreviewTransformer, BlockPreviewTransformerMap} from '../types';

class BlockPreviewTransformerRegistry {
    @observable blockPreviewTransformers: BlockPreviewTransformerMap;
    @observable priority: {[string]: number};

    constructor() {
        this.clear();
    }

    clear() {
        this.blockPreviewTransformers = {};
        this.priority = {};
    }

    has(name: string) {
        return !!this.blockPreviewTransformers[name];
    }

    add(name: string, blockPreviewTransformer: BlockPreviewTransformer, priority: number = 0) {
        if (name in this.blockPreviewTransformers) {
            throw new Error('The key "' + name + '" has already been used for another BlockPreviewTransformer');
        }

        this.blockPreviewTransformers[name] = blockPreviewTransformer;
        this.priority[name] = priority;
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

    @computed get blockPreviewTransformerKeysByPriority(): Array<string> {
        return Object.keys(this.priority)
            .sort((blockPreviewTransformerKey1, blockPreviewTransformerKey2) => {
                return this.priority[blockPreviewTransformerKey2] - this.priority[blockPreviewTransformerKey1];
            });
    }
}

export default new BlockPreviewTransformerRegistry();
