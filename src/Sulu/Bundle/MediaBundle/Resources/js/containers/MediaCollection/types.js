// @flow
export type OperationType = null | 'create' | 'update' | 'remove' | 'move';

export type OverlayType = 'overlay' | 'dialog';

export type BreadcrumbItem = {
    id: number,
    title: string,
}

export type BreadcrumbItems = Array<BreadcrumbItem>;
