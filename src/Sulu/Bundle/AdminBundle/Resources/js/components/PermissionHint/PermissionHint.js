// @flow
import React from 'react';
import {translate} from '../../utils/Translator';
import Icon from '../Icon';
import permissionHintStyles from './permissionHint.scss';

type Props = {||};

export default class PermissionHint extends React.Component<Props> {
    render() {
        return (
            <div className={permissionHintStyles.permissionHint}>
                <div className={permissionHintStyles.permissionIcon}>
                    <Icon name="su-lock" />
                </div>
                {translate('sulu_admin.no_permissions')}
            </div>
        );
    }
}
