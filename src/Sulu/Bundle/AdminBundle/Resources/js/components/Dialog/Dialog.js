// @flow
import classNames from 'classnames';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import type {Node} from 'react';
import {Portal} from 'react-portal';
import {afterElementsRendered} from '../../utils/DOM';
import Backdrop from '../Backdrop';
import Button from '../Button';
import dialogStyles from './dialog.scss';

type Props = {|
    cancelText: string,
    children: Node,
    confirmDisabled: boolean,
    confirmLoading: boolean,
    confirmText: string,
    onCancel: () => void,
    onConfirm: () => void,
    open: boolean,
    size?: 'small' | 'large',
    title: string,
|};

@observer
class Dialog extends React.Component<Props> {
    static defaultProps = {
        confirmDisabled: false,
        confirmLoading: false,
    };

    @observable open: boolean = false;
    @observable visible: boolean = false;

    constructor(props: Props) {
        super(props);

        const {open} = this.props;

        this.open = open;
        this.visible = open;
    }

    close = () => {
        this.props.onCancel();
    };

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
            title,
            children,
            confirmDisabled,
            onCancel,
            onConfirm,
            cancelText,
            confirmText,
            confirmLoading,
            size,
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

        return (
            <Fragment>
                <Backdrop open={visible} />
                {visible &&
                    <Portal>
                        <div
                            className={containerClass}
                            onTransitionEnd={this.handleTransitionEnd}
                        >
                            <div className={dialogClass}>
                                <section className={dialogStyles.content}>
                                    <header>
                                        {title}
                                    </header>
                                    <article>
                                        {children}
                                    </article>
                                    <footer>
                                        <Button onClick={onCancel} skin="secondary">
                                            {cancelText}
                                        </Button>
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

export default Dialog;
