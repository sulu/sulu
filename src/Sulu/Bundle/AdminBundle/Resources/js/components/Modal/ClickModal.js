// @flow
import React from 'react';
import type {Element, Node} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Modal from './Modal';

type Props = {
    className?: string,
    clickElement: Element<*>,
    children: Node,
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

    render() {
        return (
            <div className={this.props.className}>
                {React.cloneElement(this.props.clickElement, {onClick: this.handleElementClick})}
                <Modal isOpen={this.modalOpen} onRequestClose={this.handleRequestClose}>
                    {this.props.children}
                </Modal>
            </div>
        );
    }
}
