// @flow
import classNames from 'classnames';
import React from 'react';
import backdropStyles from './backdrop.scss';

type Props = {|
    fixed: boolean,
    onClick?: () => void,
    visible: boolean,
|};

export default class Backdrop extends React.PureComponent<Props> {
    static defaultProps = {
        fixed: true,
        visible: true,
    };

    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        const {
            visible,
            fixed,
        } = this.props;
        const backdropClass = classNames(
            backdropStyles.backdrop,
            {
                [backdropStyles.visible]: visible,
                [backdropStyles.fixed]: fixed,
            }
        );

        return <div className={backdropClass} onClick={this.handleClick} role="button" />;
    }
}
