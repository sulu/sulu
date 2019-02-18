// @flow
import React from 'react';
import type {LoadingStrategyInterface, ListAdapterProps, StructureStrategyInterface} from '../types';

export default class AbstractAdapter extends React.Component<ListAdapterProps> {
    static LoadingStrategy: Class<LoadingStrategyInterface>;

    static StructureStrategy: Class<StructureStrategyInterface>;

    static icon: string;

    static hasColumnOptions: boolean = false;
}
