// @flow
import React from 'react';
import classNames from 'classnames';
import dividerStyles from './divider.scss';

type Props = {
    children: string,
};

export default class Divider extends React.PureComponent<Props> {
    render() {
        const {children} = this.props;
        const dividerClass = classNames(
            dividerStyles.divider,
            {
                [dividerStyles.noText]: !children,
            }
        );

        return (
            <div className={dividerClass}>
                {children}
            </div>
        );
    }
}
