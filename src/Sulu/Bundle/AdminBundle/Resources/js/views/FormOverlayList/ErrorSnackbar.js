// @flow
import React from 'react';
import classNames from 'classnames';
import {translate} from '../../utils/Translator';
import Icon from '../../components/Icon';
import errorSnackbarStyles from './errorSnackbar.scss';

type Props = {|
    onCloseClick?: () => void,
    visible: boolean,
|};

const ICON = 'su-exclamation-triangle';

export default class ErrorSnackbar extends React.Component<Props> {
    static defaultProps = {
        visible: true,
    };

    render() {
        const {onCloseClick, visible} = this.props;

        const snackbarClass = classNames(
            errorSnackbarStyles.snackbar,
            {
                [errorSnackbarStyles.visible]: visible,
            }
        );

        return (
            <div className={snackbarClass}>
                <Icon className={errorSnackbarStyles.icon} name={ICON} />
                <div className={errorSnackbarStyles.text}>
                    <strong>{translate('sulu_admin.error')}</strong>
                    {onCloseClick &&
                        <button className={errorSnackbarStyles.closeButton} onClick={onCloseClick}>
                            {translate('sulu_admin.close')}
                            <Icon className={errorSnackbarStyles.closeButtonIcon} name="su-times" />
                        </button>
                    }
                </div>
            </div>
        );
    }
}
