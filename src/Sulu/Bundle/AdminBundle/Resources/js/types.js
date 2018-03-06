// @flow
import type {ComponentType, Node} from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {Types} from './containers/Form';

export type PaginationProps = {
    children: Node,
    current: ?number,
    loading: boolean,
    onChange: (page: number) => void,
    total: ?number,
};

export type PaginationAdapter = ComponentType<PaginationProps>;

export type PropertyError = {
    keyword: string,
    parameters: {[key: string]: mixed},
};

export type BlockError = Array<?{[key: string]: Error}>;

export type Error = BlockError | PropertyError;

export type ErrorCollection = {[key: string]: Error};

export type FieldTypeProps<T> = {
    error?: Error | ErrorCollection,
    fieldOptions?: Object,
    onChange: (value: T) => void,
    onFinish?: () => void,
    locale?: ?IObservableValue<string>,
    maxOccurs?: number,
    minOccurs?: number,
    options?: Object,
    showAllErrors?: boolean,
    types?: Types,
    value: ?T,
};
