// @flow
export type ButtonActionConfig = {|
    icon: string,
    label: string,
    onClick: () => void,
    type: 'button',
|};

export type DividerActionConfig = {|
    type: 'divider',
|};

export type ActionConfig = ButtonActionConfig | DividerActionConfig;
