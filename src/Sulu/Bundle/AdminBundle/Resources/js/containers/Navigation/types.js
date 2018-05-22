// @flow
export type NavigationItem = {
    childRoutes?: Array<string>,
    icon: string,
    id: string,
    items?: Array<NavigationItem>,
    label: string,
    mainRoute: string,
};
