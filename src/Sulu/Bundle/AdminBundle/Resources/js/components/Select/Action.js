// @flow
import React from 'react';
import classnames from 'classnames';
import itemStyles from './selectItem.scss';

export default class Action extends React.PureComponent {
    props: {
        disabled: boolean,
        children?: string,
        onClick: () => void,
    };

    static defaultProps = {
        disabled: false,
    };

    render() {
        const classNames = classnames({
            [itemStyles.selectItem]: true,
            [itemStyles.disabled]: this.props.disabled,
        });

        return (
            <li className={classNames}>
                <button onClick={this.props.onClick} disabled={this.props.disabled}>{this.props.children}</button>
            </li>
        );
    }
}
