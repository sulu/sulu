// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import headerStyles from './header.scss';

type Props = {
    children: Node,
    small: boolean,
};

export default class Header extends React.Component<Props> {
    static defaultProps = {
        small: false,
    };

    render() {
        const className = classNames(
            headerStyles.header,
            {
                [headerStyles.small]: this.props.small,
            }
        );

        return (
            <div className={className}>{this.props.children}</div>
        );
    }
}
