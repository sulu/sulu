// @flow
import classNames from 'classnames';
import Mousetrap from 'mousetrap';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import type {Node} from 'react';
import React from 'react';
import Portal from 'react-portal';
import Icon from '../Icon';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import type {Action} from './types';
import Actions from './Actions';
import overlayStyles from './overlay.scss';

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
export default class Overlay extends React.PureComponent<Props> {
    static defaultProps = {
        isOpen: false,
        actions: [],
    };

    @observable isVisible: boolean = false;
    @observable isOpenHasChanged: boolean = false;

    @action componentWillMount() {
        Mousetrap.bind('esc', this.close);
        this.isOpenHasChanged = this.props.isOpen;
    }

    componentWillUnmount() {
        Mousetrap.unbind('esc', this.close);
    }

    componentDidMount() {
        this.toggle();
    }

    @action componentWillReceiveProps(newProps: Props) {
        this.isOpenHasChanged = newProps.isOpen !== this.props.isOpen;
    }

    componentDidUpdate() {
        this.toggle();
    }

    close = () => {
        this.props.onRequestClose();
    };

    @action toggle() {
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
            [overlayStyles.container]: true,
            [overlayStyles.isDown]: this.isVisible,
        });
        const {isOpen, title, actions, onConfirm, confirmText, children} = this.props;

        return (
            <Portal isOpened={isOpen || this.isOpenHasChanged}>
                <div
                    className={containerClass}
                    onTransitionEnd={this.handleTransitionEnd}>
                    <div className={overlayStyles.overlay}>
                        <section className={overlayStyles.content}>
                            <header>
                                {title}
                                <Icon
                                    name={CLOSE_ICON}
                                    className={overlayStyles.icon}
                                    onClick={this.handleIconClick} />
                            </header>
                            <article>{children}</article>
                            <footer>
                                <Actions actions={actions} />
                                <button className={overlayStyles.confirmButton} onClick={onConfirm}>
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
