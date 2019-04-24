// @flow
import React from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import type {Button as ButtonConfig} from './types';
import Button from './Button';
import headerStyles from './header.scss';

const LOADER_SIZE = 24;

type Props = {
    emptyList: boolean,
    label?: string,
    leftButton?: ButtonConfig,
    loading: boolean,
    rightButton?: ButtonConfig,
};

export default class Header extends React.PureComponent<Props> {
    static defaultProps = {
        emptyList: true,
    };

    render() {
        const {
            label,
            loading,
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
                    {loading &&
                        <div className={headerStyles.loader}>
                            <Loader size={LOADER_SIZE} />
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
