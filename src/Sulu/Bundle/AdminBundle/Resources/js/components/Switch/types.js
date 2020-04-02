// @flow
import type {Node} from 'react';

export type SwitchProps<T> = {|
    checked: boolean,
    children?: Node,
    disabled: boolean,
    name?: string,
    value?: T,
|};
