// @flow
import Backdrop from '../Backdrop';
import Portal from 'react-portal';
import React from 'react';
import modalStyle from './modal.scss';

export default class Modal extends React.PureComponent {
    props: {
        isOpen: boolean,
        children: React.Element<*>,
        onRequestClose?: () => void,
    };

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
