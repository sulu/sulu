// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import buttonStyles from './button.scss';

const LOADER_SIZE = 25;

type Props = {
    active: boolean,
    children: Node,
    className?: string,
    disabled: boolean,
    loading: boolean,
    onClick: (value: *) => void,
    size: 'small' | 'large',
    skin: 'primary' | 'secondary' | 'link' | 'icon',
    value?: *,
};

export default class Button extends React.PureComponent<Props> {
    static defaultProps = {
        active: false,
        disabled: false,
        loading: false,
        size: 'large',
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
            className,
            disabled,
            loading,
            skin,
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
