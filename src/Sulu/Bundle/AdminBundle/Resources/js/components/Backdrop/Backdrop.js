// @flow
import classNames from 'classnames';
import Portal from 'react-portal';
import React from 'react';
import backdropStyles from './backdrop.scss';

type Props = {
    isOpen: boolean,
    /** When set to false the backdrop renders transparent. */
    isVisible: boolean,
    /** If true, the backdrop gets rendered in the placed element and not in the body. */
    local: boolean,
    onClick?: () => void,
};

export default class Backdrop extends React.PureComponent<Props> {
    static defaultProps = {
        isOpen: true,
        isVisible: true,
        local: false,
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
        const backdrop = <div onClick={this.handleClick} className={backdropClasses} />;
        if (this.props.local) {
            return backdrop;
        }
        return <Portal isOpened={isOpen}>{backdrop}</Portal>;
    }
}
