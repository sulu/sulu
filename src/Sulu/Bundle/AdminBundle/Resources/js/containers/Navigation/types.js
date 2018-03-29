// @flow
export type NavigationItem = {
    id: string,
    name: string,
    label: string,
    icon: string,
    mainRoute: string,
    childRoutes?: Array<string>,
    items?: Array<NavigationItem>,
};
