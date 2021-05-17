// @flow
import React from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import Button from './Button';
import headerStyles from './header.scss';
import type {Button as ButtonConfig} from './types';

const LOADER_SIZE = 24;

type Props<T, U> = {
    disabled: boolean,
    emptyList: boolean,
    label?: string,
    leftButton?: ButtonConfig<T>,
    loading: boolean,
    rightButton?: ButtonConfig<U>,
};

export default class Header<T: string | number, U: string | number> extends React.PureComponent<Props<T, U>> {
    static defaultProps = {
        disabled: false,
        emptyList: true,
    };

    render() {
        const {
            disabled,
            label,
            loading,
            emptyList,
            leftButton,
            rightButton,
        } = this.props;

        const headerClass = classNames(
            headerStyles.header,
            {
                [headerStyles.disabled]: disabled,
                [headerStyles.emptyList]: emptyList,
            }
        );

        return (
            <div className={headerClass}>
                {leftButton &&
                    <Button {...leftButton} location="left" />
                }
                <div className={headerStyles.label}>
                    {loading &&
                        <div className={headerStyles.loader}>
                            <Loader size={LOADER_SIZE} />
                        </div>
                    }
                    {!loading &&
                        label
                    }
                </div>
                {rightButton &&
                    <Button {...rightButton} location="right" />
                }
            </div>
        );
    }
}
