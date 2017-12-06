// @flow
import type {Element} from 'react';

export type PaginationProps = {
    children: Element<*>,
    current: ?number,
    loading: boolean,
    onChange: (page: number) => void,
    total: ?number,
};
