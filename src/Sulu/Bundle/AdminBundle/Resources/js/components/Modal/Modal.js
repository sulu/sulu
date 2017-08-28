// @flow
import React from 'react';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import Portal from 'react-portal';
import classNames from 'classnames';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import ModalBox from './ModalBox';
import type {OverlayProps} from './types';
import modalStyles from './modal.scss';

type Props = OverlayProps & {
    isOpen: boolean,
    onRequestClose: () => void,
};

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
        this.toggleModal();
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

    @action handleTransitionEnd = () => {
        afterElementsRendered(action(() => {
            this.isOpenHasChanged = false;
        }));
    };

    render() {
        const containerClass = classNames({
            [modalStyles.container]: true,
            [modalStyles.isDown]: this.isVisible,
        });
        const {isOpen, title, actions, onRequestClose, onConfirm, confirmText, children} = this.props;

        return (
            <Portal isOpened={isOpen || this.isOpenHasChanged}>
                <div
                    className={containerClass}
                    onTransitionEnd={this.handleTransitionEnd}>
                    <div className={modalStyles.box}>
                        <ModalBox
                            title={title}
                            actions={actions}
                            onRequestClose={onRequestClose}
                            onConfirm={onConfirm}
                            confirmText={confirmText}>
                            {children}
                        </ModalBox>
                    </div>
                    <Backdrop local={true} onClick={this.props.onRequestClose} />
                </div>
            </Portal>
        );
    }
}
