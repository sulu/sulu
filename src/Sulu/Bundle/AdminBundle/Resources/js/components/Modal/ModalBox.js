// @flow
import React from 'react';
import Icon from '../Icon';
import type {ModalProps, Action} from './types';
import modalBoxStyles from './modalBox.scss';

type Props = ModalProps & {
    onRequestClose: () => void,
}

const CLOSE_ICON = 'times';

export default class ModalBox extends React.PureComponent<Props> {
    static defaultProps = {
        actions: [],
    };

    render() {
        return (
            <div className={modalBoxStyles.box}>
                <div className={modalBoxStyles.header}>
                    {this.props.title}
                    <Icon
                        name={CLOSE_ICON}
                        className={modalBoxStyles.icon}
                        onClick={this.props.onRequestClose} />
                </div>
                <div className={modalBoxStyles.content}>
                    {this.props.children}
                </div>
                <div className={modalBoxStyles.footer}>
                    {ModalBox.renderActions(this.props.actions)}
                    <button
                        className={modalBoxStyles.confirmButton}
                        onClick={this.props.onConfirm}>{this.props.confirmText}</button>
                </div>
            </div>
        );
    }

    static renderActions(actions: Array<Action>) {
        if (actions.length > 0) {
            return (
                <div className={modalBoxStyles.actions}>
                    {actions.map(ModalBox.renderAction)}
                </div>
            );
        }
    }

    static renderAction(action: Action, index: number) {
        return (
            <button
                key={index}
                className={modalBoxStyles.action}
                onClick={action.handleAction}>{action.title}</button>
        );
    }
}
