// @flow

export type Button = {
    handle: string,
    style: string,
    icon: string,
};

export type Spacer = {};

export type ButtonItem = Button & { type: 'button' };

export type SpacerItem = Spacer & { type: 'spacer' };

export type ToolbarItem = ButtonItem | SpacerItem;

export type ToolbarConfig = Array<ToolbarItem>;
