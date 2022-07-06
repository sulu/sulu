// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {Portal} from 'react-portal';
import snackbarContainerStyles from './snackbarContainer.scss';
import type {Node} from 'react';

type Props = {|
    children: Node,
|};

@observer
class SnackbarContainer extends React.Component<Props> {
    render() {
        const {children} = this.props;

        return (
            <Portal>
                <div className={snackbarContainerStyles.container}>
                    {children}
                </div>
            </Portal>
        );
    }
}

export default SnackbarContainer;
