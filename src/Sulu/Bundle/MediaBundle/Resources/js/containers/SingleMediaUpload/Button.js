// @flow
import React from 'react';
import {Icon} from 'sulu-admin-bundle/components';
import buttonStyles from './button.scss';

export type Props = {|
    children: string,
    downloadUrl?: ?string,
    icon: string,
    onClick?: () => void,
|};

export default class Button extends React.Component<Props> {
    render() {
        const {children, downloadUrl, icon, onClick} = this.props;

        return (
            <a href={downloadUrl} className={buttonStyles.button} download={downloadUrl} onClick={onClick}>
                <Icon className={buttonStyles.icon} name={icon} />
                {children}
            </a>
        );
    }
}
