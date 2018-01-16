// @flow
import React from 'react';
import classNames from 'classnames';
import Loader from '../Loader';
import buttonStyles from './button.scss';

const LOADER_SIZE = 25;

type Props = {
    children: string,
    skin: 'primary' | 'secondary' | 'link',
    onClick: () => void,
    loading: boolean,
};

export default class Button extends React.PureComponent<Props> {
    static defaultProps = {
        loading: false,
    };

    handleClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            children,
            loading,
            skin,
        } = this.props;
        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[skin],
            {
                [buttonStyles.loading]: loading,
            }
        );

        return (
            <button className={buttonClass} onClick={this.handleClick} disabled={loading}>
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
