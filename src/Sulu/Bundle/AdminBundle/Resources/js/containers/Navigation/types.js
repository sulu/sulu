// @flow
export type NavigationItem = {
    id: string,
    title: string,
    icon: string,
    mainRoute: string,
    childRoutes?: Array<string>,
    items?: Array<NavigationItem>,
};
