// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import Button from '../Button';
import buttonStyles from './buttonGroup.scss';

type Props = {
    /* Array of Button components */
    children: ChildrenArray<Element<typeof Button>>;
};

export default class ButtonGroup extends React.PureComponent<Props> {
    cloneChildren = () => {
        const {children} = this.props;

        return React.Children.map(children, (child) => {
            return React.cloneElement(
                child,
                {
                    skin: 'icon',
                    className: buttonStyles.button,
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
