// @flow
import type {Node} from 'react';

export type CollapsibleConfig = {
    subtitle?: string,
    title: string,
    ...
};

export type CollapsibleActionConfig = {|
    icon: string,
    label: string,
    onClick: (index: number) => void,
|};

export type CollapsibleMode = 'static' | 'sortable';

export type RenderCollapsibleContentCallback<T: CollapsibleConfig>
    = (value: T, index: number, expanded: boolean) => Node;
