// @flow
import classNames from 'classnames';
import Mousetrap from 'mousetrap';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import type {Node} from 'react';
import React from 'react';
import {Portal} from 'react-portal';
import Icon from '../Icon';
import Button from '../Button';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import type {Action, Size} from './types';
import Actions from './Actions';
import overlayStyles from './overlay.scss';

type Props = {
    title: string,
    children: Node,
    actions: Array<Action>,
    confirmText: string,
    confirmLoading: boolean,
    onConfirm: () => void,
    open: boolean,
    size?: Size,
    onClose: () => void,
};

const CLOSE_ICON = 'su-times';

@observer
export default class Overlay extends React.Component<Props> {
    static defaultProps = {
        open: false,
        actions: [],
        confirmLoading: false,
    };

    @observable visible: boolean = false;
    @observable openHasChanged: boolean = false;

    constructor(props: Props) {
        super(props);

        Mousetrap.bind('esc', this.close);
        this.openHasChanged = this.props.open;
    }

    componentWillUnmount() {
        Mousetrap.unbind('esc');
    }

    componentDidMount() {
        this.toggle();
    }

    @action componentDidUpdate(prevProps: Props) {
        this.openHasChanged = prevProps.open !== this.props.open;
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
            actions,
            children,
            confirmLoading,
            confirmText,
            onClose,
            onConfirm,
            open,
            title,
            size,
        } = this.props;
        const containerClass = classNames(
            overlayStyles.container,
            {
                [overlayStyles.isDown]: this.visible,
            }
        );

        const overlayClass = classNames(
            overlayStyles.overlay,
            {
                [overlayStyles[size]]: size,
            }
        );

        const showPortal = open || this.openHasChanged;

        return (
            <div>
                <Backdrop open={showPortal} onClick={onClose} />
                {showPortal &&
                    <Portal>
                        <div
                            className={containerClass}
                            onTransitionEnd={this.handleTransitionEnd}
                        >
                            <div className={overlayClass}>
                                <section className={overlayStyles.content}>
                                    <header>
                                        {title}
                                        <Icon
                                            name={CLOSE_ICON}
                                            className={overlayStyles.icon}
                                            onClick={this.handleIconClick}
                                        />
                                    </header>
                                    <article>{children}</article>
                                    <footer>
                                        <Actions actions={actions} />
                                        <Button skin="primary" onClick={onConfirm} loading={confirmLoading}>
                                            {confirmText}
                                        </Button>
                                    </footer>
                                </section>
                            </div>
                        </div>
                    </Portal>
                }
            </div>
        );
    }
}
