// @flow
import React from 'react';
import viewStyles from './view.scss';
import type {Node} from 'react';

type Props = {
    children: Node,
};

class View extends React.Component<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <div className={viewStyles.view}>
                {children}
            </div>
        );
    }
}

export default View;
