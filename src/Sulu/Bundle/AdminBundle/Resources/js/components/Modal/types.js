// @flow
import type {Node} from 'react';

export type Action = {
    title: string,
    handleAction: () => void,
};

export type ModalProps = {
    title: string,
    children: Node,
    actions: Array<Action>,
    confirmText: string,
    onConfirm: () => void,
}
