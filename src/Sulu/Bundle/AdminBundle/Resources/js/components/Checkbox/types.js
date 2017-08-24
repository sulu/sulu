// @flow
import type {Node} from 'react';

export type CheckboxProps = {
    checked: boolean,
    value?: string | number,
    name?: string,
    onChange?: (checked: boolean, value?: string | number) => void,
    children?: Node,
};
