// @flow
export type Button<T: string | number> = {|
    disabled?: boolean,
    icon?: string,
    label?: string,
    onClick: (value: ?T) => void,
    options?: Array<ButtonOption<T>>,
|};

export type ButtonOption<T> = {|
    icon?: string,
    label: string,
    value: T,
|};
