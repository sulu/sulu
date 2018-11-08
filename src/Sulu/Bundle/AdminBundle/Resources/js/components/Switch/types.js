// @flow
import type {Node} from 'react';

export type SwitchProps = {|
    checked: boolean,
    disabled: boolean,
    value?: string | number,
    name?: string,
    children?: Node,
|};
