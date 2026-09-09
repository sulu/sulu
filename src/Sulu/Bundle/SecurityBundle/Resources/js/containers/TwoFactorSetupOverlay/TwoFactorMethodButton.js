// @flow
import React from 'react';
import {Icon} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import twoFactorSetupOverlayStyles from './twoFactorSetupOverlay.scss';

const METHOD_ICONS = {
    email: 'su-envelope',
    google: 'su-mobile',
    totp: 'su-mobile',
};

type Props = {|
    disabled: boolean,
    method: string,
    onClick: (method: string) => void,
|};

class TwoFactorMethodButton extends React.Component<Props> {
    handleClick = () => {
        const {method, onClick} = this.props;

        onClick(method);
    };

    render() {
        const {disabled, method} = this.props;

        return (
            <button
                className={twoFactorSetupOverlayStyles.method}
                disabled={disabled}
                onClick={this.handleClick}
                type="button"
            >
                <Icon
                    className={twoFactorSetupOverlayStyles.methodIcon}
                    name={METHOD_ICONS[method] || 'su-lock'}
                />
                <span className={twoFactorSetupOverlayStyles.methodText}>
                    <span className={twoFactorSetupOverlayStyles.methodTitle}>
                        {translate('sulu_security.two_factor_method_' + method)}
                    </span>
                    <span className={twoFactorSetupOverlayStyles.methodDescription}>
                        {translate('sulu_security.two_factor_method_' + method + '_description')}
                    </span>
                </span>
                <Icon className={twoFactorSetupOverlayStyles.methodArrow} name="su-angle-right" />
            </button>
        );
    }
}

export default TwoFactorMethodButton;
