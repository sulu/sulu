// @flow
import type {Node} from 'react';

export type SwitchProps = {|
    checked: boolean,
    children?: Node,
    disabled: boolean,
    name?: string,
    value?: string | number,
|};
