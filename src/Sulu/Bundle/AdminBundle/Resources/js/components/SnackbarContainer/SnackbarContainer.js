// @flow
import React from 'react';
import classnames from 'classnames';
import {observer} from 'mobx-react';
import {Portal} from 'react-portal';
import snackbarContainerStyles from './snackbarContainer.scss';
import type {Node} from 'react';

type Props = {|
    children: Node,
    className?: string,
|};

@observer
class SnackbarContainer extends React.Component<Props> {
    render() {
        const {children, className} = this.props;

        return (
            <Portal>
                <div className={classnames(snackbarContainerStyles.container, className)}>
                    {children}
                </div>
            </Portal>
        );
    }
}

export default SnackbarContainer;
