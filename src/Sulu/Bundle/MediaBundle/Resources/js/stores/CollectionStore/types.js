// @flow
export type BreadcrumbItem = {
    id: number,
    title: string,
}

export type BreadcrumbItems = Array<BreadcrumbItem>;

export type Collection =  {
    id: ?number,
    parentId: ?number,
    breadcrumb: ?BreadcrumbItems,
};
