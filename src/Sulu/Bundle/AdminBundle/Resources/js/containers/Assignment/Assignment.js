// @flow
import React from 'react';
import {MultiItemSelection} from '../../components';

type Props = {
    icon: string,
};

export default class Assignment extends React.Component<Props> {
    static defaultProps = {
        icon: 'su-plus',
    };

    handleOverlayOpen = () => {
        // TODO implement overlay
    };

    render() {
        const {icon} = this.props;

        return (
            <MultiItemSelection
                leftButton={{
                    icon,
                    onClick: this.handleOverlayOpen,
                }}
            />
        );
    }
}
