// @flow
import classNames from 'classnames';
import Mousetrap from 'mousetrap';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import {Portal} from 'react-portal';
import Icon from '../Icon';
import Button from '../Button';
import {afterElementsRendered} from '../../utils/DOM';
import Backdrop from '../Backdrop';
import Snackbar, {type SnackbarType} from '../Snackbar';
import Actions from './Actions';
import overlayStyles from './overlay.scss';
import type {Action, Size} from './types';
import type {Node} from 'react';

type Props = {
    actions: Array<Action>,
    children: Node,
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText: string,
    onClose: () => void,
    onConfirm: () => void,
    onSnackbarClick?: () => void,
    onSnackbarCloseClick?: () => void,
    open: boolean,
    size?: Size,
    snackbarMessage?: string,
    snackbarType: SnackbarType,
    title: string,
};

const CLOSE_ICON = 'su-times';
const CLOSE_OVERLAY_KEY = 'esc';

@observer
class Overlay extends React.Component<Props> {
    static defaultProps = {
        actions: [],
        confirmDisabled: false,
        confirmLoading: false,
        snackbarType: 'error',
    };

    @observable open: boolean = false;
    @observable visible: boolean = false;
    @observable snackbarType: SnackbarType;

    constructor(props: Props) {
        super(props);

        const {open, snackbarType} = this.props;

        if (open) {
            Mousetrap.bind(CLOSE_OVERLAY_KEY, this.close);
        }

        this.open = open;
        this.visible = open;
        this.snackbarType = snackbarType;
    }

    componentWillUnmount() {
        if (this.props.open) {
            Mousetrap.unbind(CLOSE_OVERLAY_KEY);
        }
    }

    @action componentDidUpdate(prevProps: Props) {
        const {open, snackbarMessage, snackbarType} = this.props;

        if (prevProps.open !== open) {
            if (open) {
                Mousetrap.bind(CLOSE_OVERLAY_KEY, this.close);
            } else {
                Mousetrap.unbind(CLOSE_OVERLAY_KEY);
            }

            afterElementsRendered(action(() => {
                this.open = open;
            }));
        }

        if (prevProps.open === false && open === true) {
            this.visible = true;
        }

        if (snackbarMessage && this.snackbarType !== snackbarType) {
            this.snackbarType = snackbarType;
        }
    }

    close = () => {
        this.props.onClose();
    };

    @action handleTransitionEnd = () => {
        const {open} = this.props;
        if (!open) {
            this.visible = false;
        }
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
            onConfirm,
            onSnackbarClick,
            onSnackbarCloseClick,
            size,
            snackbarMessage,
            title,
        } = this.props;

        const {open, visible} = this;

        const containerClass = classNames(
            overlayStyles.container,
            {
                [overlayStyles.isDown]: open,
            }
        );

        const overlayClass = classNames(
            overlayStyles.overlay,
            {
                [overlayStyles[size]]: size,
            }
        );

        return (
            <Fragment>
                {visible &&
                    <Portal>
                        <Backdrop />
                        <div
                            className={containerClass}
                            onTransitionEnd={this.handleTransitionEnd}
                        >
                            <div className={overlayClass}>
                                <section className={overlayStyles.content}>
                                    <header className={overlayStyles.header}>
                                        <h2>{title}</h2>
                                        <Icon
                                            className={overlayStyles.icon}
                                            name={CLOSE_ICON}
                                            onClick={this.handleIconClick}
                                        />
                                    </header>
                                    <article className={overlayStyles.article}>{children}</article>
                                    <footer className={overlayStyles.footer}>
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
                                    <div className={overlayStyles.snackbar}>
                                        <Snackbar
                                            message={snackbarMessage || ''}
                                            onClick={onSnackbarClick}
                                            onCloseClick={onSnackbarCloseClick}
                                            type={this.snackbarType}
                                            visible={!!snackbarMessage}
                                        />
                                    </div>
                                </section>
                            </div>
                        </div>
                    </Portal>
                }
            </Fragment>
        );
    }
}

export default Overlay;
