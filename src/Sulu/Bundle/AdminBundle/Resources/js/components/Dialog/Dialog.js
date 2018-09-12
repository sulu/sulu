// @flow
import classNames from 'classnames';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import type {Node} from 'react';
import {Portal} from 'react-portal';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import Button from '../Button';
import dialogStyles from './dialog.scss';

type Props = {|
    confirmDisabled: boolean,
    children: Node,
    cancelText: string,
    confirmText: string,
    confirmLoading: boolean,
    onConfirm: () => void,
    onCancel: () => void,
    open: boolean,
    size?: 'small' | 'large',
    title: string,
|};

@observer
export default class Dialog extends React.Component<Props> {
    static defaultProps = {
        confirmDisabled: false,
        confirmLoading: false,
        open: false,
    };

    @observable visible: boolean = false;
    @observable openHasChanged: boolean = false;

    constructor(props: Props) {
        super(props);

        this.openHasChanged = this.props.open;
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
        this.props.onCancel();
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

    render() {
        const {
            open,
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
        const containerClass = classNames(
            dialogStyles.dialogContainer,
            {
                [dialogStyles.open]: this.visible,
            }
        );

        const showPortal = open || this.openHasChanged;

        const dialogClass = classNames(
            dialogStyles.dialog,
            {
                [dialogStyles[size]]: size,
            }
        );

        return (
            <div>
                <Backdrop open={showPortal} />
                {showPortal &&
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
            </div>
        );
    }
}
