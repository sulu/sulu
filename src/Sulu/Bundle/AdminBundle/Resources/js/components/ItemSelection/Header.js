// @flow
import React from 'react';
import type {Node} from 'react';
import type {Button as ButtonConfig} from './types';
import Button from './Button';
import headerStyles from './header.scss';

type Props = {
    label?: string,
    children?: Node,
    leftButton?: ButtonConfig,
    rightButton?: ButtonConfig,
};

export default class ItemSelection extends React.PureComponent<Props> {
    render() {
        const {
            label,
            children,
            leftButton,
            rightButton,
        } = this.props;

        return (
            <div className={headerStyles.header}>
                {leftButton &&
                    <Button {...leftButton} location="left" />
                }
                <div className={headerStyles.content}>
                    {children ||
                        <div className={headerStyles.label}>
                            {label}
                        </div>
                    }
                </div>
                {rightButton &&
                    <Button {...rightButton} location="right" />
                }
            </div>
        );
    }
}
