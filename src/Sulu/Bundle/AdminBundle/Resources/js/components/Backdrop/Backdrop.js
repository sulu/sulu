// @flow
import classNames from 'classnames';
import Portal from 'react-portal';
import React from 'react';
import backdropStyles from './backdrop.scss';

type Props = {
    open: boolean,
    /** When set to false the backdrop renders transparent. */
    visible: boolean,
    /** If true, the backdrop gets rendered in the placed element and not in the body. */
    local: boolean,
    onClick?: () => void,
};

export default class Backdrop extends React.PureComponent<Props> {
    static defaultProps = {
        open: true,
        visible: true,
        local: false,
    };

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        const {
            open,
            visible,
        } = this.props;
        const backdropClass = classNames(
            backdropStyles.backdrop,
            {
                [backdropStyles.visible]: visible,
            }
        );
        const backdrop = <div onClick={this.handleClick} className={backdropClass} />;

        if (this.props.local) {
            return backdrop;
        }

        return <Portal isOpened={open}>{backdrop}</Portal>;
    }
}
