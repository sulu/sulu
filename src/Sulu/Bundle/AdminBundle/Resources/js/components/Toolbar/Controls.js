// @flow
import type {ChildrenArray, Element, Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import type {Group, Item, Skins} from './types';
import controlsStyles from './controls.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item> | Element<typeof Group>>,
    skin: ?Skins,
};

export default class Controls extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
    };

    static createChildren(children: ChildrenArray<Element<typeof Item> | Element<typeof Group>>, skin: Skins) {
        return React.Children.map(children, (child) => {
            if (!child) {
                return;
            }

            return React.cloneElement(
                child,
                {
                    ...child.props,
                    skin: skin,
                }
            );
        });
    }

    render() {
        const {
            skin,
            children,
        } = this.props;

        const controlsClass = classNames(
            controlsStyles.controls,
            controlsStyles[skin]
        );

        return (
            <div className={controlsClass}>
                {Controls.createChildren(children, skin)}
            </div>
        );
    }
}
