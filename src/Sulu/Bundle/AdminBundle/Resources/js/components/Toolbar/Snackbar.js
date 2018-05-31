// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import Icon from '../Icon';
import snackbarStyles from './snackbar.scss';

type Props = {|
    onCloseClick: () => void,
|};

export default class Snackbar extends React.Component<Props> {
    render() {
        const {onCloseClick} = this.props;

        const snackbarClass = classNames(
            snackbarStyles.snackbar,
            snackbarStyles.error
        );

        return (
            <div className={snackbarClass}>
                <Icon className={snackbarStyles.icon} name="su-exclamation-triangle" />
                <div className={snackbarStyles.text}>
                    <strong>{translate('sulu_admin.error')}</strong>
                    <button className={snackbarStyles.closeButton} onClick={onCloseClick}>
                        {translate('sulu_admin.close')}
                        <Icon className={snackbarStyles.closeButtonIcon} name="su-times" />
                    </button>
                </div>
            </div>
        );
    }
}
