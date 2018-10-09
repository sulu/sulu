// @flow
import type {ChildrenArray} from 'react';
import React from 'react';
import classNames from 'classnames';
import type {Group, Item, Skin} from './types';
import controlsStyles from './controls.scss';

type Props = {
    children: ChildrenArray<Item | Group | false>,
    grow?: boolean,
    skin?: Skin,
};

export default class Controls extends React.PureComponent<Props> {
    static defaultProps = {
        grow: false,
        skin: 'light',
    };

    static createChildren(children: ChildrenArray<Item | Group | false>, skin?: Skin) {
        return React.Children.map(children, (child: Item | Group | false) => {
            if (!child) {
                return;
            }

            // $FlowFixMe
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
            children,
            grow,
            skin,
        } = this.props;

        const controlsClass = classNames(
            controlsStyles.controls,
            controlsStyles[skin],
            {
                [controlsStyles.grow]: grow,
            }
        );

        return (
            <div className={controlsClass}>
                {Controls.createChildren(children, skin)}
            </div>
        );
    }
}
