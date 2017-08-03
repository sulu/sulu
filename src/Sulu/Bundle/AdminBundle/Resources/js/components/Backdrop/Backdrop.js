// @flow
import Portal from 'react-portal';
import React from 'react';
import backdropStyles from './backdrop.scss';
import classNames from 'classnames';

export default class Backdrop extends React.PureComponent {
    props: {
        isOpen: boolean,
        /** When set to false the backdrop renders transparent. */
        isVisible: boolean,
        onClick?: () => void,
    };

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
