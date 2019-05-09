// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import Icon from '../Icon';
import snackbarStyles from './snackbar.scss';

type Props = {|
    onClick?: () => void,
    onCloseClick?: () => void,
    type: 'error' | 'success',
    visible: boolean,
|};

const ICONS = {
    error: 'su-exclamation-triangle',
    success: 'su-check',
};

export default class Snackbar extends React.Component<Props> {
    static defaultProps = {
        visible: true,
    };

    render() {
        const {onCloseClick, onClick, type, visible} = this.props;

        const snackbarClass = classNames(
            snackbarStyles.snackbar,
            snackbarStyles[type],
            {
                [snackbarStyles.clickable]: onClick,
                [snackbarStyles.visible]: visible,
            }
        );

        return (
            <div className={snackbarClass} onClick={onClick}>
                <Icon className={snackbarStyles.icon} name={ICONS[type]} />
                {type !== 'success' &&
                    <div className={snackbarStyles.text}>
                        <strong>{translate('sulu_admin.' + type)}</strong>
                        {onCloseClick &&
                            <button className={snackbarStyles.closeButton} onClick={onCloseClick}>
                                {translate('sulu_admin.close')}
                                <Icon className={snackbarStyles.closeButtonIcon} name="su-times" />
                            </button>
                        }
                    </div>
                }
            </div>
        );
    }
}
