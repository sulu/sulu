// @flow
import {action, observable} from 'mobx';
import Modal from './Modal';
import React from 'react';
import {observer} from 'mobx-react';

@observer
export default class ClickModal extends React.PureComponent {
    props: {
        className?: string,
        clickElement: React.Element<*>,
        children: React.Element<*>,
    };

    @observable modalOpen = false;

    @action handleElementClick = () => {
        this.modalOpen = true;
    };

    @action handleModalRequestClose = () => {
        this.modalOpen = false;
    };

    render() {
        return (
            <div className={this.props.className}>
                {React.cloneElement(this.props.clickElement, {onClick: this.handleElementClick})}
                <Modal isOpen={this.modalOpen} onRequestClose={this.handleModalRequestClose}>
                    {this.props.children}
                </Modal>
            </div>
        );
    }
}
