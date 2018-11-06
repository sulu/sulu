// @flow
import classNames from 'classnames';
import Mousetrap from 'mousetrap';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import type {Node} from 'react';
import React, {Fragment} from 'react';
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
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText: string,
    onConfirm: () => void,
    open: boolean,
    size?: Size,
    onClose: () => void,
};

const CLOSE_ICON = 'su-times';
const CLOSE_OVERLAY_KEY = 'esc';

@observer
export default class Overlay extends React.Component<Props> {
    static defaultProps = {
        actions: [],
        confirmDisabled: false,
        confirmLoading: false,
    };

    @observable visible: boolean = false;
    @observable openHasChanged: boolean = false;

    constructor(props: Props) {
        super(props);

        if (this.props.open) {
            Mousetrap.bind(CLOSE_OVERLAY_KEY, this.close);
        }
        this.openHasChanged = this.props.open;
    }

    componentWillUnmount() {
        if (this.props.open) {
            Mousetrap.unbind(CLOSE_OVERLAY_KEY);
        }
    }

    componentDidMount() {
        this.toggle();
    }

    @action componentWillReceiveProps(nextProps: Props) {
        if (nextProps.open !== this.props.open) {
            this.openHasChanged = true;
        }
    }

    @action componentDidUpdate(prevProps: Props) {
        this.toggle();

        if (prevProps.open !== this.props.open) {
            if (this.props.open) {
                Mousetrap.bind(CLOSE_OVERLAY_KEY, this.close);
            } else {
                Mousetrap.unbind(CLOSE_OVERLAY_KEY);
            }
        }
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
            confirmDisabled,
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
            <Fragment>
                <Backdrop onClick={onClose} open={showPortal} />
                {showPortal &&
                    <Portal>
                        <div
                            className={containerClass}
                            onTransitionEnd={this.handleTransitionEnd}
                        >
                            <div className={overlayClass}>
                                <section className={overlayStyles.content}>
                                    <header>
                                        <h2>{title}</h2>
                                        <Icon
                                            className={overlayStyles.icon}
                                            name={CLOSE_ICON}
                                            onClick={this.handleIconClick}
                                        />
                                    </header>
                                    <article>{children}</article>
                                    <footer>
                                        <Actions actions={actions} />
                                        <Button
                                            disabled={confirmDisabled}
                                            loading={confirmLoading}
                                            onClick={onConfirm}
                                            skin="primary"
                                        >
                                            {confirmText}
                                        </Button>
                                    </footer>
                                </section>
                            </div>
                        </div>
                    </Portal>
                }
            </Fragment>
        );
    }
}
