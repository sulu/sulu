// @flow
import React from 'react';
import classNames from 'classnames';
import iconsStyles from './icons.scss';
import type {ChildrenArray, Node} from 'react';
import type {Skin} from './types';

type Props = {
    children: ChildrenArray<Node>,
    skin?: Skin,
};

export default class Icons extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
    };

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
                {React.Children.map(children, (child) => (
                    <div className={iconsStyles.icon}>
                        {child}
                    </div>
                ))}
            </div>
        );
    }
}
