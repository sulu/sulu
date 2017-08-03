// @flow
import React from 'react';
import Portal from 'react-portal';
import backdropStyles from './backdrop.scss';
import classNames from 'classnames';

export default class Backdrop extends React.PureComponent {
    props: {
        isOpen: boolean,
        isVisible?: boolean,
        onClick?: () => void,
    };

    static defaultProps = {
        isVisible: true,
        onClick: () => {},
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
                <div 
                    onClick={onClick}
                    className={backdropClasses}
                />
            </Portal>
        );
    }
}
