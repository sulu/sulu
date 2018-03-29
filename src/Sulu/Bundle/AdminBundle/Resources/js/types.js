// @flow
import type {ComponentType, Node} from 'react';
import {FormInspector} from './containers/Form';
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

export type FieldTypeProps<T> = {|
    error?: Error | ErrorCollection,
    fieldTypeOptions?: Object,
    formInspector?: FormInspector,
    maxOccurs?: number,
    minOccurs?: number,
    onChange: (value: T) => void,
    onFinish?: () => void,
    schemaOptions?: Object,
    showAllErrors?: boolean,
    types?: Types,
    value: ?T,
|};
