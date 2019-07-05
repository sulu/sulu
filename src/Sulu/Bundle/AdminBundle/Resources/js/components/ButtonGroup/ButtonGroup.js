// @flow
import React from 'react';
import classNames from 'classnames';
import type {ChildrenArray, Element} from 'react';
import Button from '../Button';
import buttonGroupStyles from './buttonGroup.scss';

type Props = {
    children: ChildrenArray<Element<typeof Button>>,
};

export default class ButtonGroup extends React.PureComponent<Props> {
    cloneChildren = () => {
        const {children} = this.props;

        return React.Children.map(children, (child) => {
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
            <div>
                {this.cloneChildren()}
            </div>
        );
    }
}
