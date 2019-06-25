// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import TargetGroupRulesComponent from '../../../containers/TargetGroupRules';
import type {Rule} from '../../../containers/TargetGroupRules/types';

class TargetGroupRules extends React.Component<FieldTypeProps<Array<Rule>>> {
    handleChange = (value: Array<Rule>) => {
        const {onChange, onFinish} = this.props;
        onChange(value);
        onFinish();
    };

    render() {
        const {value} = this.props;
        return <TargetGroupRulesComponent onChange={this.handleChange} value={value || []} />;
    }
}

export default TargetGroupRules;
