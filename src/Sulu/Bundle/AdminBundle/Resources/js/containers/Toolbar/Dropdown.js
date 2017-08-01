// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import dropdownStyles from './dropdown.scss';

export default class Dropdown extends React.PureComponent {
    static defaultProps = {
        isOpen: false,
        onItemSelected: () => {}
    };

    handleClick(item) {
        const {onClick} = item;
        const {onSelected} = this.props;

        if (onClick) {
            onClick();
        }

        onItemSelected(item);
    }

    render() {
        const {isOpen, items} = this.props;
        const dropdownContainerClasses = classNames({
            [dropdownStyles.container]: true,
            [dropdownStyles.isOpen]: isOpen,
        });

        return (
            <div className={dropdownContainerClasses}>
                <ul className={dropdownStyles.items}>
                    {
                        items.map((item) => (
                            <li key={item.title}>
                                <button className={dropdownStyles.item} onClick={() => this.handleClick(item)}>
                                    {item.title}
                                </button>
                            </li>
                        ))
                    }
                </ul>
            </div>
        );
    }
}
