// @flow
export type NavigationItem = {
    childViews?: Array<string>,
    icon: string,
    id: string,
    items?: Array<NavigationItem>,
    label: string,
    view: string,
};
