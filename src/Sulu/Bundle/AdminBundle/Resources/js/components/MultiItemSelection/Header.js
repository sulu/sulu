// @flow
import React from 'react';
import classNames from 'classnames';
import type {Button as ButtonConfig} from './types';
import Button from './Button';
import headerStyles from './header.scss';

type Props = {
    label?: string,
    emptyList: boolean,
    leftButton?: ButtonConfig,
    rightButton?: ButtonConfig,
};

export default class Header extends React.PureComponent<Props> {
    static defaultProps = {
        emptyList: true,
    };

    render() {
        const {
            label,
            emptyList,
            leftButton,
            rightButton,
        } = this.props;
        const headerClass = classNames(
            headerStyles.header,
            {
                [headerStyles.emptyList]: emptyList,
            }
        );

        return (
            <div className={headerClass}>
                {leftButton &&
                    <Button {...leftButton} location="left" />
                }
                <div className={headerStyles.label}>
                    {label}
                </div>
                {rightButton &&
                    <Button {...rightButton} location="right" />
                }
            </div>
        );
    }
}
