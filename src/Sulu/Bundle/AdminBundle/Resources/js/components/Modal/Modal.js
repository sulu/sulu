// @flow
import React from 'react';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import Portal from 'react-portal';
import classnames from 'classnames';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import ModalBox from './ModalBox';
import type {ModalProps} from './types';
import modalStyles from './modal.scss';

type Props = ModalProps & {
    isOpen: boolean,
    onRequestClose: () => void,
};

const ESC_KEY = 27;

@observer
export default class Modal extends React.PureComponent<Props> {
    static defaultProps = {
        isOpen: false,
    };

    @observable isVisible: boolean = false;
    @observable isOpenHasChanged: boolean = false;

    @action componentWillMount() {
        this.isOpenHasChanged = this.props.isOpen;
    }

    componentDidMount() {
        window.addEventListener('keydown', this.handleKeyDown);
        this.toggleModal();
    }

    componentWillUnmount() {
        window.removeEventListener('keydown', this.handleKeyDown);
    }

    @action componentWillReceiveProps(newProps: Props) {
        this.isOpenHasChanged = newProps.isOpen !== this.props.isOpen;
    }

    componentDidUpdate() {
        this.toggleModal();
    }

    @action toggleModal() {
        afterElementsRendered(action(() => {
            if (this.isOpenHasChanged) {
                this.isVisible = this.props.isOpen;
            }
        }));
    }

    handleKeyDown = (event: KeyboardEvent) => {
        if (event.keyCode === ESC_KEY) {
            this.props.onRequestClose();
        }
    };

    @action handleTransitionEnd = () => {
        afterElementsRendered(action(() => {
            this.isOpenHasChanged = false;
        }));
    };

    render() {
        const containerClasses = classnames({
            [modalStyles.container]: true,
            [modalStyles.isDown]: this.isVisible,
        });

        return (
            <div>
                <Portal isOpened={this.props.isOpen || this.isOpenHasChanged}>
                    <div
                        className={containerClasses}
                        onTransitionEnd={this.handleTransitionEnd} >
                        <div className={modalStyles.box}>
                            <ModalBox
                                title={this.props.title}
                                actions={this.props.actions}
                                onRequestClose={this.props.onRequestClose}
                                onConfirm={this.props.onConfirm}
                                confirmText={this.props.confirmText} >
                                {this.props.children}
                            </ModalBox>
                        </div>
                        <Backdrop inPortal={false} onClick={this.props.onRequestClose} />
                    </div>
                </Portal>
            </div>
        );
    }
}
