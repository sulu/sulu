// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {Skin} from './types';
import iconsStyles from './icons.scss';

type Props = {
    children: ChildrenArray<Element<typeof Icon>>,
    skin?: Skin,
};

export default class Icons extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
    };

    static createChildren(children: ChildrenArray<Element<typeof Icon>>) {
        return React.Children.map(children, (child) => {
            return React.cloneElement(
                child,
                {
                    ...child.props,
                    className: iconsStyles.icon,
                }
            );
        });
    }

    render() {
        const {
            skin,
            children,
        } = this.props;

        const iconsClass = classNames(
            iconsStyles.icons,
            iconsStyles[skin]
        );

        return (
            <div className={iconsClass}>
                {Icons.createChildren(children)}
            </div>
        );
    }
}
