// @flow
import React from 'react';
import type {Node} from 'react';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import Portal from 'react-portal';
import classNames from 'classnames';
import Icon from '../Icon';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import type {Action} from './types';
import Actions from './Actions';
import modalStyles from './modal.scss';
import modalBoxStyles from './modalBox.scss';

type Props = {
    title: string,
    children: Node,
    actions: Array<Action>,
    confirmText: string,
    onConfirm: () => void,
    isOpen: boolean,
    onRequestClose: () => void,
};

const CLOSE_ICON = 'times';

@observer
export default class Modal extends React.PureComponent<Props> {
    static defaultProps = {
        isOpen: false,
        actions: [],
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

    close() {
        this.props.onRequestClose();
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

    handleIconClick = () => {
        this.close();
    };

    render() {
        const containerClass = classNames({
            [modalStyles.container]: true,
            [modalStyles.isDown]: this.isVisible,
        });
        const {isOpen, title, actions, onConfirm, confirmText, children} = this.props;

        return (
            <Portal isOpened={isOpen || this.isOpenHasChanged}>
                <div
                    className={containerClass}
                    onTransitionEnd={this.handleTransitionEnd}>
                    <div className={modalStyles.box}>
                        <section className={modalBoxStyles.box}>
                            <header>
                                {title}
                                <Icon
                                    name={CLOSE_ICON}
                                    className={modalBoxStyles.icon}
                                    onClick={this.handleIconClick} />
                            </header>
                            <article>{children}</article>
                            <footer>
                                <Actions actions={actions} />
                                <button className={modalBoxStyles.confirmButton} onClick={onConfirm}>
                                    {confirmText}
                                </button>
                            </footer>
                        </section>
                    </div>
                    <Backdrop local={true} onClick={this.props.onRequestClose} />
                </div>
            </Portal>
        );
    }
}
