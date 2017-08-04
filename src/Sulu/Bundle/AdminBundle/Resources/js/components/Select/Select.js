// @flow
import Icon from '../Icon';
import Action from './Action';
import Divider from './Divider';
import Option from './Option';
import React from 'react';
import selectStyles from './select.scss';

export default class Select extends React.PureComponent {
    props: {
        value?: string,
        onChange?: () => void,
        children: Array<Option | Action | Divider>,
    };

    static defaultProps = {
        children: [],
    };

    render() {
        return (
            <div>
                <button className={selectStyles.button}>
                    Marcel Moosbrugger
                    <Icon className={selectStyles.icon} name="chevron-down" />
                </button>
                <ul className={selectStyles.dropdown}>{this.props.children}</ul>
            </div>
        );
    }
}
