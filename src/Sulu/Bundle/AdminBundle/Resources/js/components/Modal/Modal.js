// @flow
import React from 'react';
import type {Node} from 'react';
import Portal from 'react-portal';
import Backdrop from '../Backdrop';
import modalStyle from './modal.scss';

type Props = {
    isOpen: boolean,
    children: Node,
    onRequestClose?: () => void,
};

export default class Modal extends React.PureComponent<Props> {
    static defaultProps = {
        isOpen: false,
    };

    requestClose = () => {
        if (this.props.onRequestClose) {
            this.props.onRequestClose();
        }
    };

    handleBackdropClick = this.requestClose;

    render() {
        return (
            <div>
                <Portal isOpened={this.props.isOpen}>
                    <div className={modalStyle.container}>
                        <div className={modalStyle.modal}>{this.props.children}</div>
                    </div>
                </Portal>
                <Backdrop isOpen={this.props.isOpen} onClick={this.handleBackdropClick} />
            </div>
        );
    }
}
