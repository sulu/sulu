// @flow

export type BreadcrumbItem = {
    id: string | number,
    title: string,
}

export type BreadcrumbItems = Array<BreadcrumbItem>;

export type Collection =  {
    parentId: ?string | number,
    breadcrumb: ?BreadcrumbItems,
};
