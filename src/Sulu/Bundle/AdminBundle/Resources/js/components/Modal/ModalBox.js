// @flow
import React from 'react';
import keydown from 'react-keydown';
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

    @keydown('esc') requestClose() {
        this.props.onRequestClose();
    }

    handleIconClick = () => this.requestClose();

    render() {
        const {title, children, actions, onConfirm, confirmText} = this.props;

        return (
            <section className={modalBoxStyles.box}>
                <header>
                    {title}
                    <Icon name={CLOSE_ICON} className={modalBoxStyles.icon} onClick={this.handleIconClick} />
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
