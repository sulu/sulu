// @flow
import type {ButtonActionConfig, DividerActionConfig} from '../Block/types';
import type {Node} from 'react';

export type BlockButtonActionConfig = ButtonActionConfig & { onClick: (index: number) => void,
};
export type BlockActionConfig = BlockButtonActionConfig | DividerActionConfig ;

export type RenderBlockContentCallback<T: string, U: {type: T}>
    = (value: U, type: T, index: number, expanded: boolean) => Node;

export type BlockMode = 'static' | 'sortable' | 'selectable';

export type Message = {
    icon?: string,
    text: string,
    type: 'success' | 'error' | 'warning' | 'info',
};
