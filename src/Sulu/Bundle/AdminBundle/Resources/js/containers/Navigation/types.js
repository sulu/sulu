// @flow
export type Navigation = {
    title: string,
    icon: string,
    mainRoute: string,
    childRoutes: Array<string>,
    items: Array<Navigation>,
};
