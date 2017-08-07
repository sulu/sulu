// @flow
import React from 'react';
import dropdownStyles from './dropdown.scss';

export default class Dropdown extends React.PureComponent {
    props: {
        isOpen: boolean,
        children?: React.Element<*>,
    };

    static defaultProps = {
        isOpen: false,
    };

    render() {
        return (
            <ul className={dropdownStyles.dropdown}>
                {this.props.children}
            </ul>
        );
    }
}
