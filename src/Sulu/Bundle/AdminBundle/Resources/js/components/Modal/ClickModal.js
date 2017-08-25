// @flow
import React from 'react';
import type {Element} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {ModalProps} from './types';
import Modal from './Modal';

type Props = ModalProps & {
    className?: string,
    clickElement: Element<*>,
};

@observer
export default class ClickModal extends React.PureComponent<Props> {
    @observable modalOpen = false;

    @action handleElementClick = () => {
        this.modalOpen = true;
    };

    @action handleRequestClose = () => {
        this.modalOpen = false;
    };

    @action handleConfirm = () => {
        this.props.onConfirm();
        this.modalOpen = false;
    };

    render() {
        const {className, clickElement, children, ...modalProps} = this.props;
        delete modalProps.onConfirm;
        return (
            <div className={className}>
                {React.cloneElement(clickElement, {onClick: this.handleElementClick})}
                <Modal
                    isOpen={this.modalOpen}
                    onRequestClose={this.handleRequestClose}
                    onConfirm={this.handleConfirm}
                    {...modalProps}>
                    {children}
                </Modal>
            </div>
        );
    }
}
