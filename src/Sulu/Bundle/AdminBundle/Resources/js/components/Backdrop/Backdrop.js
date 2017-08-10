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

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        const {
            isOpen,
            isVisible,
        } = this.props;
        const backdropClasses = classNames({
            [backdropStyles.backdrop]: true,
            [backdropStyles.isVisible]: isVisible,
        });

        const body = document.body;
        if (body) {
            if (isOpen) {
                body.classList.add(backdropStyles.preventScrolling);
            } else {
                body.classList.remove(backdropStyles.preventScrolling);
            }
        }

        return (
            <Portal isOpened={isOpen}>
                <div
                    onClick={this.handleClick}
                    className={backdropClasses} />
            </Portal>
        );
    }
}
