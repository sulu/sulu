// @flow
import type {ElementRef} from 'react';

export type InputProps<T: ?string | ?number> = {|
    collapsed?: boolean,
    name?: string,
    icon?: string,
    type: string,
    loading?: boolean,
    placeholder?: string,
    labelRef?: (ref: ?ElementRef<'label'>) => void,
    inputRef?: (ref: ?ElementRef<'input'>) => void,
    valid: boolean,
    value: ?T,
    onBlur?: () => void,
    onChange: (value: ?string, event: SyntheticEvent<HTMLInputElement>) => void,
    onClearClick?: () => void,
    onIconClick?: () => void,
    onKeyPress?: (key: ?string, event: SyntheticKeyboardEvent<HTMLInputElement>) => void,
    iconStyle?: Object,
    iconClassName?: string,
    skin?: 'default' | 'dark',
    min?: T,
    max?: T,
    step?: T,
|};
