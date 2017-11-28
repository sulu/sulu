// @flow
import React from 'react';
import type {DatagridAdapterProps} from '../types';

export default class AbstractAdapter extends React.Component<DatagridAdapterProps> {
    static getLoadingStrategy: () => string = () => { throw Error('Not implemented'); };
    static getStorageStrategy: () => string = () => { throw Error('Not implemented'); };
}
