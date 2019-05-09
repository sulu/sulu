// @flow
export type Size = 'small' | 'medium' | 'large';

export type SidebarConfig = {|
    defaultSize?: Size,
    props?: Object,
    sizes?: Array<Size>,
    view: string,
|};
