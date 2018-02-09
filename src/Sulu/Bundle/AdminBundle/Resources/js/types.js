// @flow
import type {ComponentType, Element} from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named

export type PaginationProps = {
    children: Element<*>,
    current: ?number,
    loading: boolean,
    onChange: (page: number) => void,
    total: ?number,
};

export type PaginationAdapter = ComponentType<PaginationProps>;

export type FieldTypeProps<T> = {
    onChange: (value: T) => void,
    locale?: ?IObservableValue<string>,
    options?: Object,
    value: ?T,
};
