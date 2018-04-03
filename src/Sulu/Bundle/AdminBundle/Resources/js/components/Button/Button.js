// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import buttonStyles from './button.scss';

const LOADER_SIZE = 25;

type Props = {
    active: boolean,
    size: 'small' | 'large',
    children: Node,
    disabled: boolean,
    skin: 'primary' | 'secondary' | 'link' | 'icon',
    onClick: (value: *) => void,
    loading: boolean,
    className?: string,
    value?: *,
};

export default class Button extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        loading: false,
        size: 'large',
        active: false,
        skin: 'secondary',
    };

    handleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.onClick(this.props.value);
    };

    render() {
        const {
            active,
            children,
            disabled,
            loading,
            skin,
            className,
        } = this.props;
        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[skin],
            {
                [buttonStyles.loading]: loading,
                [buttonStyles.active]: active,
            },
            className
        );

        return (
            <button className={buttonClass} onClick={this.handleClick} disabled={loading || disabled} type="button">
                <span className={buttonStyles.text}>{children}</span>
                {loading &&
                    <div className={buttonStyles.loader}>
                        <Loader size={LOADER_SIZE} />
                    </div>
                }
            </button>
        );
    }
}
