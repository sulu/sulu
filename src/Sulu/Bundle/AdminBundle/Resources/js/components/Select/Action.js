// @flow
import React from 'react';
import optionStyles from './option.scss';

export default class Action extends React.PureComponent {
    props: {
        disabled?: boolean,
        children?: string,
        onClick: () => void,
    };

    render() {
        return (
            <li className={optionStyles.option}>
                <button>{this.props.children}</button>
            </li>
        );
    }
}
