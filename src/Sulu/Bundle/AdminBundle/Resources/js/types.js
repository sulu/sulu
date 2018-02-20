// @flow
import type {ComponentType, Element} from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {Types} from './containers/Form';

export type PaginationProps = {
    children: Element<*>,
    current: ?number,
    loading: boolean,
    onChange: (page: number) => void,
    total: ?number,
};

export type PaginationAdapter = ComponentType<PaginationProps>;

export type FieldTypeProps<T> = {
    error?: Object,
    onChange: (value: T) => void,
    locale?: ?IObservableValue<string>,
    maxOccurs?: number,
    minOccurs?: number,
    options?: Object,
    types?: Types,
    value: ?T,
};
