// @flow
import classNames from 'classnames';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import {Portal} from 'react-portal';
import {afterElementsRendered} from '../../utils/DOM';
import Backdrop from '../Backdrop';
import Button from '../Button';
import Snackbar from '../Snackbar';
import dialogStyles from './dialog.scss';
import type {Node} from 'react';

type Props = {|
    align: 'left' | 'center',
    cancelText?: string,
    children: Node,
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText: string,
    error?: string,
    onCancel?: () => void,
    onConfirm: () => void,
    onSnackbarClick?: () => void,
    onSnackbarCloseClick?: () => void,
    open: boolean,
    size?: 'small' | 'large',
    title: string,
    warning?: string,
|};

@observer
class Dialog extends React.Component<Props> {
    static defaultProps = {
        align: 'center',
        confirmDisabled: false,
        confirmLoading: false,
    };

    @observable open: boolean = false;
    @observable visible: boolean = false;
    @observable snackbarType: 'error' | 'warning' = 'error';

    constructor(props: Props) {
        super(props);

        const {open, error, warning} = this.props;

        this.open = open;
        this.visible = open;
        this.snackbarType = warning && !error ? 'warning' : 'error';
    }

    @action componentDidUpdate(prevProps: Props) {
        const {open, error, warning} = this.props;

        if (prevProps.open === false && open === true) {
            this.visible = true;
        }

        if (prevProps.open !== open) {
            afterElementsRendered(action(() => {
                this.open = open;
            }));
        }

        if (prevProps.error !== error || prevProps.warning !== warning) {
            if (error) {
                this.snackbarType = 'error';
            } else if (warning) {
                this.snackbarType = 'warning';
            }
        }
    }

    @action handleTransitionEnd = () => {
        const {open} = this.props;
        if (!open) {
            this.visible = false;
        }
    };

    @computed get snackbarMessage(): ?string {
        const {error, warning} = this.props;

        switch (this.snackbarType) {
            case 'error':
                return error || undefined;
            case 'warning':
                return warning || undefined;
        }

        return undefined;
    }

    render() {
        const {
            align,
            children,
            confirmDisabled,
            cancelText,
            confirmLoading,
            confirmText,
            error,
            onCancel,
            onConfirm,
            onSnackbarClick,
            onSnackbarCloseClick,
            size,
            title,
            warning,
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
                                            message={this.snackbarMessage || ''}
                                            onClick={onSnackbarClick}
                                            onCloseClick={onSnackbarCloseClick}
                                            type={this.snackbarType}
                                            visible={!!(error || warning)}
                                        />
                                    </div>

                                    <header className={dialogStyles.header}>
                                        {title}
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
