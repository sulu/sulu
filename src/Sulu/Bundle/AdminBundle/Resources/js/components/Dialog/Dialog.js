// @flow
import classNames from 'classnames';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import {Portal} from 'react-portal';
import {afterElementsRendered} from '../../utils/DOM';
import Backdrop from '../Backdrop';
import Button from '../Button';
import Snackbar, {type SnackbarType} from '../Snackbar';
import dialogStyles from './dialog.scss';
import type {Node} from 'react';

type Props = {|
    align: 'left' | 'center',
    cancelText?: string,
    children: Node,
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText: string,
    onCancel?: () => void,
    onConfirm: () => void,
    onSnackbarClick?: () => void,
    onSnackbarCloseClick?: () => void,
    open: boolean,
    size?: 'small' | 'large',
    snackbarMessage?: string,
    snackbarType: SnackbarType,
    title: string,
|};

@observer
class Dialog extends React.Component<Props> {
    static defaultProps = {
        align: 'center',
        confirmDisabled: false,
        confirmLoading: false,
        snackbarType: 'error',
    };

    @observable open: boolean = false;
    @observable visible: boolean = false;

    constructor(props: Props) {
        super(props);

        const {open} = this.props;

        this.open = open;
        this.visible = open;
    }

    @action componentDidUpdate(prevProps: Props) {
        const {open} = this.props;

        if (prevProps.open === false && open === true) {
            this.visible = true;
        }

        if (prevProps.open !== open) {
            afterElementsRendered(action(() => {
                this.open = open;
            }));
        }
    }

    @action handleTransitionEnd = () => {
        const {open} = this.props;
        if (!open) {
            this.visible = false;
        }
    };

    render() {
        const {
            align,
            children,
            confirmDisabled,
            cancelText,
            confirmLoading,
            confirmText,
            onCancel,
            onConfirm,
            onSnackbarClick,
            onSnackbarCloseClick,
            size,
            snackbarMessage,
            snackbarType,
            title,
        } = this.props;

        const {open, visible} = this;

        const containerClass = classNames(
            dialogStyles.dialogContainer,
            {
                [dialogStyles.open]: open,
            }
        );

        const dialogClass = classNames(
            dialogStyles.dialog,
            {
                [dialogStyles[size]]: size,
            }
        );

        const articleStyle = classNames(
            dialogStyles.article,
            {
                [dialogStyles[align]]: align,
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
                            <div className={dialogClass}>
                                <section className={dialogStyles.content}>
                                    <div className={dialogStyles.snackbar}>
                                        <Snackbar
                                            message={snackbarMessage || ''}
                                            onClick={onSnackbarClick}
                                            onCloseClick={onSnackbarCloseClick}
                                            type={snackbarType}
                                            visible={!!snackbarMessage}
                                        />
                                    </div>

                                    <header className={dialogStyles.header}>
                                        <span className={dialogStyles.headerItem}>
                                            {title}
                                        </span>
                                    </header>
                                    <article className={articleStyle}>
                                        {children}
                                    </article>
                                    <footer className={dialogStyles.footer}>
                                        <Button
                                            disabled={confirmDisabled}
                                            loading={confirmLoading}
                                            onClick={onConfirm}
                                            skin="primary"
                                        >
                                            {confirmText}
                                        </Button>
                                        {onCancel && cancelText &&
                                            <Button onClick={onCancel} skin="secondary">
                                                {cancelText}
                                            </Button>
                                        }
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

export default Dialog;
