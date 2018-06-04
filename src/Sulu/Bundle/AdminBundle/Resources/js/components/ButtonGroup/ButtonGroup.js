// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Button from '../Button';
import buttonGroupStyles from './buttonGroup.scss';

type Props = {
    /** Array of Button components */
    children: ChildrenArray<Element<typeof Button>>,
};

export default class ButtonGroup extends React.PureComponent<Props> {
    cloneChildren = () => {
        const {children} = this.props;

        return React.Children.map(children, (child) => {
            return React.cloneElement(
                child,
                {
                    className: buttonGroupStyles.button,
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
