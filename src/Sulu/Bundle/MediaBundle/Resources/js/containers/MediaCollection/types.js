// @flow
export type OperationType = null | 'create' | 'update' | 'remove' | 'move' | 'permissions';

export type OverlayType = 'overlay' | 'dialog';

export type BreadcrumbItem = {
    id: number,
    title: string,
}

export type BreadcrumbItems = Array<BreadcrumbItem>;
