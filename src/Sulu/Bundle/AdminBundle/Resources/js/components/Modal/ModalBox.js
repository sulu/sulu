// @flow
import React from 'react';
import Icon from '../Icon';
import type {ModalProps} from './types';
import Actions from './Actions';
import modalBoxStyles from './modalBox.scss';

type Props = ModalProps & {
    onRequestClose: () => void,
}

const CLOSE_ICON = 'times';

export default class ModalBox extends React.PureComponent<Props> {
    static defaultProps = {
        actions: [],
    };

    render() {
        const {title, onRequestClose, children, actions, onConfirm, confirmText} = this.props;

        return (
            <section className={modalBoxStyles.box}>
                <header>
                    {title}
                    <Icon name={CLOSE_ICON} className={modalBoxStyles.icon} onClick={onRequestClose} />
                </header>
                <article>{children}</article>
                <footer>
                    <Actions actions={actions} />
                    <button className={modalBoxStyles.confirmButton} onClick={onConfirm}>{confirmText}</button>
                </footer>
            </section>
        );
    }
}
