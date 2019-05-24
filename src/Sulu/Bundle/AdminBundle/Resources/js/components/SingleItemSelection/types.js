// @flow
export type Button<T> = {|
    disabled?: boolean,
    icon: string,
    onClick: (value: ?T) => void,
    options?: Array<ButtonOption<T>>,
|};

type ButtonOption<T> = {
    label: string,
    value: T,
};
