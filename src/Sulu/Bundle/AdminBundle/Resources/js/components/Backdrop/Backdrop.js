// @flow
import classNames from 'classnames';
import Portal from 'react-portal';
import React from 'react';
import backdropStyles from './backdrop.scss';

type Props = {
    isOpen: boolean,
    /** When set to false the backdrop renders transparent. */
    isVisible: boolean,
    onClick?: () => void,
};

export default class Backdrop extends React.PureComponent<Props> {
    static defaultProps = {
        isVisible: true,
    };

    render() {
        const {
            isOpen,
            isVisible,
            onClick,
        } = this.props;
        const backdropClasses = classNames({
            [backdropStyles.backdrop]: true,
            [backdropStyles.isVisible]: isVisible,
        });

        return (
            <Portal isOpened={isOpen}>
                <div onClick={onClick} className={backdropClasses} />
            </Portal>
        );
    }
}
