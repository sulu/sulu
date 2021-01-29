// @flow
import React from 'react';
import type {LoadingStrategyInterface, ListAdapterProps, StructureStrategyInterface} from '../types';

export default class AbstractAdapter extends React.Component<ListAdapterProps> {
    static StructureStrategy: Class<StructureStrategyInterface>;

    static icon: string;

    static hasColumnOptions: boolean = false;

    static searchable: boolean = true;

    static paginatable: boolean = false;

    // eslint-disable-next-line no-unused-vars
    static getLoadingStrategy(options: Object = {}): Class<LoadingStrategyInterface> {
        throw new Error('The method AbstractAdapter::getLoadingStrategy must be overridden!');
    }
}
