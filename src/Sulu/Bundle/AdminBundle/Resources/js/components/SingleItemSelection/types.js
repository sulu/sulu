// @flow
export type Button<T> = {|
    disabled?: boolean,
    icon: string,
    onClick: (value: ?T) => void,
    options?: Array<ButtonOption<T>>,
|};

export type ItemButton = {|
    disabled?: boolean,
    icon: string,
    onClick: () => void,
|};

type ButtonOption<T> = {|
    icon?: string,
    label: string,
    value: T,
|};
