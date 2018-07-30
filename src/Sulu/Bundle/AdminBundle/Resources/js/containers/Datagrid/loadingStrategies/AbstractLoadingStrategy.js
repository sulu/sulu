// @flow
import type {LoadingStrategyInterface, LoadOptions, ItemEnhancer, StructureStrategyInterface} from '../types';

export default class AbstractLoadingStrategy implements LoadingStrategyInterface {
    structureStrategy: StructureStrategyInterface;

    setStructureStrategy(structureStrategy: StructureStrategyInterface) {
        this.structureStrategy = structureStrategy;
    }

    // eslint-disable-next-line no-unused-vars
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object> {
        throw new Error('Not implemented');
    }
}
