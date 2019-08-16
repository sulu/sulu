// @flow
import React from 'react';
import classNames from 'classnames';
import type {ChildrenArray, Element} from 'react';
import Button from '../Button';
import DropdownButton from '../DropdownButton';
import buttonGroupStyles from './buttonGroup.scss';

type Props = {
    children: ChildrenArray<Element<typeof Button | typeof DropdownButton> | false>,
};

export default class ButtonGroup extends React.PureComponent<Props> {
    cloneChildren = () => {
        const {children} = this.props;

        return React.Children.map(children, (child) => {
            if (!child) {
                return null;
            }

            const buttonClass = classNames(
                buttonGroupStyles.button,
                child.props.className
            );

            return React.cloneElement(
                child,
                {
                    className: buttonClass,
                    skin: 'icon',
                }
            );
        });
    };

    render() {
        return (
            <div className={buttonGroupStyles.buttonGroup}>
                {this.cloneChildren()}
            </div>
        );
    }
}
