// @flow
import {RequestPromise} from '../../../services/Requester';
import type {LoadingStrategyInterface, LoadOptions, StructureStrategyInterface} from '../types';

export default class AbstractLoadingStrategy implements LoadingStrategyInterface {
    structureStrategy: StructureStrategyInterface;

    setStructureStrategy(structureStrategy: StructureStrategyInterface) {
        this.structureStrategy = structureStrategy;
    }

    // eslint-disable-next-line no-unused-vars
    load(resourceKey: string, options: LoadOptions): RequestPromise<Object> {
        throw new Error('Not implemented');
    }
}
