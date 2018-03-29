// @flow
export type NavigationItem = {
    id: string,
    label: string,
    icon: string,
    mainRoute: string,
    childRoutes?: Array<string>,
    items?: Array<NavigationItem>,
};
