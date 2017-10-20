// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import treeStyles from './tree.scss';

type Props = {
    children?: Node,
    className?: string,
    /** Called when column was clicked */
    onClick?: () => void,
};

export default class HeaderCell extends React.PureComponent<Props> {
    handleOnClick = () => {
        if (this.props.onClick) {
            this.props.onClick();
        }
    };

    render() {
        const {
            onClick,
            children,
            className,
        } = this.props;
        const headerCellClass = classNames(
            className,
            treeStyles.headerCell,
            {
                [treeStyles.clickable]: !!onClick,
            }
        );

        return (
            <div className={headerCellClass}>
                {!onClick &&
                    <span>{children}</span>
                }
                {onClick &&
                    <button
                        onClick={this.handleOnClick}
                    >
                        <span>{children}</span>
                    </button>
                }
            </div>
        );
    }
}
