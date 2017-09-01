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
    open: boolean,
    onClose: () => void,
};

const CLOSE_ICON = 'times';

@observer
export default class Overlay extends React.PureComponent<Props> {
    static defaultProps = {
        open: false,
        actions: [],
    };

    @observable visible: boolean = false;
    @observable openHasChanged: boolean = false;

    @action componentWillMount() {
        Mousetrap.bind('esc', this.close);
        this.openHasChanged = this.props.open;
    }

    componentWillUnmount() {
        Mousetrap.unbind('esc', this.close);
    }

    componentDidMount() {
        this.toggle();
    }

    @action componentWillReceiveProps(newProps: Props) {
        this.openHasChanged = newProps.open !== this.props.open;
    }

    componentDidUpdate() {
        this.toggle();
    }

    close = () => {
        this.props.onClose();
    };

    @action toggle() {
        afterElementsRendered(action(() => {
            if (this.openHasChanged) {
                this.visible = this.props.open;
            }
        }));
    }

    @action handleTransitionEnd = () => {
        afterElementsRendered(action(() => {
            this.openHasChanged = false;
        }));
    };

    handleIconClick = () => {
        this.close();
    };

    render() {
        const {
            open,
            title,
            actions,
            onClose,
            children,
            onConfirm,
            confirmText,
        } = this.props;
        const containerClass = classNames(
            overlayStyles.container,
            {
                [overlayStyles.isDown]: this.visible,
            }
        );

        return (
            <Portal isOpened={open || this.openHasChanged}>
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
                    <Backdrop local={true} onClick={onClose} />
                </div>
            </Portal>
        );
    }
}
