// @flow
import React from 'react';
import optionStyles from './option.scss';


export default class Option extends React.PureComponent {
    props: {
        selected?: boolean,
        disabled?: boolean,
        value?: string,
        children?: string,
    };

    render() {
        return (
            <li className={optionStyles.option}>
                <button>{this.props.children}</button>
            </li>
        );
    }
}
