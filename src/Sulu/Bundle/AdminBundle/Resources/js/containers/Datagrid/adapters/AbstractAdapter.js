// @flow
import React from 'react';
import type {LoadingStrategyInterface, DatagridAdapterProps, StructureStrategyInterface} from '../types';

export default class AbstractAdapter extends React.Component<DatagridAdapterProps> {
    static getLoadingStrategy(): LoadingStrategyInterface {
        throw Error('Not implemented');
    }

    static getStructureStrategy(): StructureStrategyInterface {
        throw Error('Not implemented');
    }
}
