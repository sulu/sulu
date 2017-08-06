// @flow
import type {BackButtonType} from './types';
import Icon from '../../components/Icon';
import React from 'react';
import buttonStyles from './button.scss';

const ICON_ARROW_LEFT = 'arrow-left';

export default class BackButton extends React.PureComponent {
    props: BackButtonType;

    handleOnClick = () => {
        this.props.onClick();
    };

    render() {
        return (
            <button className={buttonStyles.backButton} onClick={this.handleOnClick}>
                <Icon name={ICON_ARROW_LEFT} className={buttonStyles.backButtonIcon} />
            </button>
        );
    }
}
