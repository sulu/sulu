// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import Icon from '../Icon';
import snackbarStyles from './snackbar.scss';

type Props = {|
    onCloseClick?: () => void,
    onClick?: () => void,
    type: 'error' | 'success',
|};

const ICONS = {
    error: 'su-exclamation-triangle',
    success: 'su-check',
};

export default class Snackbar extends React.Component<Props> {
    render() {
        const {onCloseClick, onClick, type} = this.props;

        const snackbarClass = classNames(
            snackbarStyles.snackbar,
            snackbarStyles[type]
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
