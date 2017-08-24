// @flow
import type {Node} from 'react';

export type Action = {
    title: string,
    onClick: () => void,
};

export type ModalProps = {
    title: string,
    children: Node,
    actions: Array<Action>,
    confirmText: string,
    onConfirm: () => void,
}
