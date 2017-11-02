// @flow
import classNames from 'classnames';
import {observable, action} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Portal from 'react-portal';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import Button from '../Button';
import dialogStyles from './dialog.scss';

type Props = {
    open: boolean,
    title: string,
    children: string,
    cancelText: string,
    confirmText: string,
    onConfirm: () => void,
    onCancel: () => void,
};

@observer
export default class Dialog extends React.PureComponent<Props> {
    static defaultProps = {
        open: false,
    };

    @observable visible: boolean = false;
    @observable openHasChanged: boolean = false;

    @action componentWillMount() {
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
            onCancel,
            onConfirm,
            cancelText,
            confirmText,
        } = this.props;
        const containerClass = classNames(
            dialogStyles.container,
            {
                [dialogStyles.isOpen]: this.visible,
            }
        );

        return (
            <Portal isOpened={open || this.openHasChanged}>
                <div
                    className={containerClass}
                    onTransitionEnd={this.handleTransitionEnd}
                >
                    <div className={dialogStyles.dialog}>
                        <section className={dialogStyles.content}>
                            <header>
                                {title}
                            </header>
                            <article>
                                {children}
                            </article>
                            <footer>
                                <Button type="cancel" onClick={onCancel}>
                                    {cancelText}
                                </Button>
                                <Button type="confirm" onClick={onConfirm}>
                                    {confirmText}
                                </Button>
                            </footer>
                        </section>
                    </div>
                    <Backdrop local={true} />
                </div>
            </Portal>
        );
    }
}
