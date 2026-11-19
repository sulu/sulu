// @flow
import {observer} from 'mobx-react';
import {action} from 'mobx';
import React, {Fragment} from 'react';
import Snackbar from '../../components/Snackbar';
import ToolbarStore from './stores/ToolbarStore';

type Props = {|
    toolbarStore: ToolbarStore,
|};

@observer
class ToolbarSnackbars extends React.Component<Props> {
    @action handleErrorSnackbarCloseClick = () => {
        this.props.toolbarStore.errors.pop();
    };

    render() {
        const {toolbarStore} = this.props;
        const {errors, warnings} = toolbarStore;
        const lastError = errors[errors.length - 1];
        const lastWarning = warnings[warnings.length - 1];

        return (
            <Fragment>
                <Snackbar
                    actions={typeof lastError === 'object' ? lastError.actions : undefined}
                    message={typeof lastError === 'object' ? lastError.message : lastError}
                    onCloseClick={this.handleErrorSnackbarCloseClick}
                    title={typeof lastError === 'object' ? lastError.title : undefined}
                    type="error"
                    visible={errors.length > 0}
                />
                <Snackbar
                    actions={typeof lastWarning === 'object' ? lastWarning.actions : undefined}
                    message={typeof lastWarning === 'object' ? lastWarning.message : lastWarning}
                    onCloseClick={toolbarStore.config?.onWarningCloseClick}
                    title={typeof lastWarning === 'object' ? lastWarning.title : undefined}
                    type="warning"
                    visible={warnings.length > 0}
                />
            </Fragment>
        );
    }
}

export default ToolbarSnackbars;
