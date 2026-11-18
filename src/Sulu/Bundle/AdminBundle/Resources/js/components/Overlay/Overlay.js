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
import ToolbarButton from '../Toolbar/Button';
import Actions from './Actions';
import overlayStyles from './overlay.scss';
import type {Action, Size} from './types';
import type {Node} from 'react';

type Props = {
    actions: Array<Action>,
    children: Node,
    closable: boolean,
    closeSuccessMessage?: string,
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText?: string,
    footer?: Node,
    onClose: () => void,
    onConfirm?: () => void,
    onSnackbarClick?: () => void,
    onSnackbarCloseClick?: () => void,
    open: boolean,
    size?: Size,
    snackbar?: Node,
    snackbarMessage?: string,
    snackbarType: SnackbarType,
    title: string,
    toolbar?: Node,
};

const CLOSE_ICON = 'su-times';
const SUCCESS_ICON = 'su-check';
const CLOSE_OVERLAY_KEY = 'esc';

// Mousetrap keeps one handler per key and unbinds it for everybody. With overlays on top of each other
// (e.g. a view in a view overlay opening an overlay of its own) the one that closes first would take
// the esc key from the one below. So the close handlers of the open overlays are stacked: the topmost
// one is bound, and the one below is bound again when it is removed.
const closeHandlers = [];

function addCloseHandler(handler: () => void) {
    closeHandlers.push(handler);
    Mousetrap.bind(CLOSE_OVERLAY_KEY, handler);
}

function removeCloseHandler(handler: () => void) {
    const index = closeHandlers.indexOf(handler);
    if (index === -1) {
        return;
    }

    closeHandlers.splice(index, 1);

    if (closeHandlers.length > 0) {
        Mousetrap.bind(CLOSE_OVERLAY_KEY, closeHandlers[closeHandlers.length - 1]);
    } else {
        Mousetrap.unbind(CLOSE_OVERLAY_KEY);
    }
}

@observer
class Overlay extends React.Component<Props> {
    static defaultProps = {
        actions: [],
        closable: true,
        confirmDisabled: false,
        confirmLoading: false,
        snackbarType: 'error',
    };

    @observable open: boolean = false;
    @observable visible: boolean = false;

    constructor(props: Props) {
        super(props);

        const {closable, open} = this.props;

        if (open && closable) {
            addCloseHandler(this.close);
        }

        this.open = open;
        this.visible = open;
    }

    componentWillUnmount() {
        if (this.props.open && this.props.closable) {
            removeCloseHandler(this.close);
        }
    }

    @action componentDidUpdate(prevProps: Props) {
        const {closable, open} = this.props;

        if (prevProps.open !== open || prevProps.closable !== closable) {
            if (open && closable) {
                addCloseHandler(this.close);
            } else {
                removeCloseHandler(this.close);
            }
        }

        if (prevProps.open !== open) {
            afterElementsRendered(action(() => {
                this.open = open;
            }));
        }

        if (prevProps.open === false && open === true) {
            this.visible = true;
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
            closable,
            closeSuccessMessage,
            confirmDisabled,
            confirmLoading,
            confirmText,
            footer,
            onConfirm,
            onSnackbarClick,
            onSnackbarCloseClick,
            size,
            snackbar,
            snackbarMessage,
            snackbarType,
            title,
            toolbar,
        } = this.props;

        const footerVisible = footer !== undefined || onConfirm !== undefined || actions.length > 0;

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
                                <section
                                    aria-label={toolbar ? title : undefined}
                                    className={classNames(
                                        overlayStyles.content,
                                        {[overlayStyles.withToolbar]: toolbar}
                                    )}
                                    role={toolbar ? 'dialog' : undefined}
                                >
                                    {toolbar
                                        ? (
                                            <div className={overlayStyles.toolbar}>
                                                <div className={overlayStyles.toolbarActions}>{toolbar}</div>
                                                {closable &&
                                                    <div
                                                        className={classNames(
                                                            overlayStyles.closeButton,
                                                            {[overlayStyles.closeButtonSuccess]: closeSuccessMessage}
                                                        )}
                                                    >
                                                        <ToolbarButton
                                                            icon={closeSuccessMessage ? SUCCESS_ICON : CLOSE_ICON}
                                                            onClick={this.handleIconClick}
                                                            success={!!closeSuccessMessage}
                                                        />
                                                    </div>
                                                }
                                                <span className={overlayStyles.liveRegion} role="status">
                                                    {closeSuccessMessage}
                                                </span>
                                            </div>
                                        )
                                        : (
                                            <header className={overlayStyles.header}>
                                                <h2>{title}</h2>
                                                {closable &&
                                                    <Icon
                                                        className={overlayStyles.icon}
                                                        name={CLOSE_ICON}
                                                        onClick={this.handleIconClick}
                                                    />
                                                }
                                            </header>
                                        )
                                    }
                                    <div className={overlayStyles.snackbar}>
                                        <Snackbar
                                            message={snackbarMessage || ''}
                                            onClick={onSnackbarClick}
                                            onCloseClick={onSnackbarCloseClick}
                                            type={snackbarType}
                                            visible={!!snackbarMessage}
                                        />
                                        {snackbar}
                                    </div>
                                    <article className={overlayStyles.article}>{children}</article>
                                    {footerVisible &&
                                        <footer className={overlayStyles.footer}>
                                            {footer !== undefined ? footer : (
                                                <Fragment>
                                                    {actions.length > 0 && (
                                                        <Actions actions={actions} />
                                                    )}
                                                    {onConfirm !== undefined && (
                                                        <Button
                                                            disabled={confirmDisabled}
                                                            loading={confirmLoading}
                                                            onClick={onConfirm}
                                                            skin="primary"
                                                        >
                                                            {confirmText}
                                                        </Button>
                                                    )}
                                                </Fragment>
                                            )}
                                        </footer>
                                    }
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
